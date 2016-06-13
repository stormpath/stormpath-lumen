<?php
/*
 * Copyright 2015 Stormpath, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Stormpath\Lumen\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Validation\Factory as Validator;
use Stormpath\Lumen\Http\Traits\AuthenticatesUser;
use Event;
use Stormpath\Lumen\Exceptions\ActionAbortedException;
use Stormpath\Lumen\Events\UserIsRegistering;
use Stormpath\Lumen\Events\UserHasRegistered;
use Stormpath\Lumen\Http\Traits\Cookies;
use Stormpath\Resource\Account;
use Symfony\Component\HttpFoundation\Cookie;

class RegisterController extends Controller
{
    use AuthenticatesUser, Cookies;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Validator
     */
    private $validator;


    /**
     * LoginController constructor.
     * @param Request $request
     * @param Validator $validator
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->validator = app('validator');;
    }

    public function getRegister()
    {
        return $this->respondWithForm();
    }

    public function postRegister()
    {
        $validator = $this->registerValidator();

        if($validator->fails()) {
            return $this->respondWithValidationErrorForJson($validator);
        }

        if(($errorFields = $this->isAcceptedPostFields($this->request->all())) !== true) {
            return $this->respondWithErrorJson('We do not allow arbitrary data to be posted to an account\'s custom data object. `'. array_shift($errorFields) . '` is either disabled or not defined in the config.', 400);
        }


        try {
            $registerFields = $this->setRegisterFields();
//            dd($registerFields);
            $account = \Stormpath\Resource\Account::instantiate($registerFields);

            app('cache.store')->forget('stormpath.application');
            $application = app('stormpath.application');

            $account = $application->createAccount($account);

            $customDataAdded = false;

            foreach ($registerFields as $key=>$value) {

                if ($key!='password' && $key!='confirmPassword') {
                    if ($account->{$key}!=$registerFields[$key]) {
                        $account->customData->{$key} = $value;
                        $customDataAdded = true;
                    }
                }
            }

            if ($customDataAdded) {
                $account->save();
            }

            if(config('stormpath.web.register.autoLogin') == false) {
                return $this->respondWithAccount($account, new Cookie('access'), new Cookie('refresh'));
            }

            $login = isset($registerFields['username']) ? $registerFields['username'] : null;
            $login = isset($registerFields['email']) ? $registerFields['email'] : $login;

            $result = $this->authenticate($login, $registerFields['password']);
            $accessTokenCookie = $this->makeAccessTokenCookie($result->getAccessTokenString());
            $refreshTokenCookie = $this->makeRefreshTokenCookie($result->getRefreshTokenString());

            return $this->respondWithAccount($account, $accessTokenCookie, $refreshTokenCookie);


        } catch(\Stormpath\Resource\ResourceError $re) {
            return $this->respondWithErrorJson($re->getMessage(), $re->getStatus());
        }
    }

    private function registerValidator()
    {
        $rules = [];
        $messages = [];

        $registerField = config('stormpath.web.register.form.fields');

        foreach($registerField as $key => $field) {
            if($field['enabled'] == true && $field['required'] == true) {
                $rules[$key] = 'required';
            }
        }

        $messages['username.required'] = 'Username is required.';
        $messages['givenName.required'] = 'Given name is required.';
        $messages['middleName.required'] = 'Middle name is required.';
        $messages['surname.required'] = 'Surname is required.';
        $messages['email.required'] = 'Email is required.';
        $messages['password.required'] = 'Password is required.';
        $messages['confirmPassword.required'] = 'Password confirmation is required.';


        if( config('stormpath.web.register.form.fields.confirmPassword.enabled') ) {
            $rules['password'] = 'required|same:confirmPassword';
            $messages['password.same'] = 'Passwords are not the same.';
        }

        $validator = $this->validator->make(
            $this->request->all(),
            $rules,
            $messages
        );


        return $validator;
    }

    private function setRegisterFields()
    {
        $registerArray = [];
        $registerFields = config('stormpath.web.register.form.fields');
        foreach($registerFields as $spfield=>$field) {
            if($field['required'] == true) {
                $registerArray[$spfield] = $this->request->input($spfield);
            }
        }

        return $registerArray;
    }

    private function respondWithForm()
    {
        $fields = [];

        foreach(config('stormpath.web.register.form.fields') as $field) {
            if($field['enabled'] == true) {
                $fields[] = $field;
            }
        }

        $data = [
            'form' => [
                'fields' => $fields
            ],
            'accountStores' => app('cache.store')->get('stormpath.accountStores')
        ];

        return response()->json($data);
    }


    private function respondWithErrorJson($message, $statusCode = 400)
    {
        $error = [
            'message' => $message,
            'status' => $statusCode
        ];

        return response()->json($error, $statusCode);
    }


    private function respondWithAccount(Account $account, Cookie $accessTokenCookie, Cookie $refreshTokenCookie)
    {
        $properties = ['account'=>[]];
        $config = config('stormpath.web.me.expand');
        $whiteListResources = [];
        foreach($config as $item=>$value) {
            if($value == true) {
                $whiteListResources[] = $item;
            }
        }

        $propNames = $account->getPropertyNames();
        foreach($propNames as $prop) {
            $property = $this->getPropertyValue($account, $prop);

            if(is_object($property) && !in_array($prop, $whiteListResources)) {
                continue;
            }

            $properties['account'][$prop] = $property;
        }

        return response()->json($properties)->cookie($accessTokenCookie)->cookie($refreshTokenCookie);
    }

    private function getPropertyValue($account, $prop)
    {
        $value = null;
        try {
            $value = $account->getProperty($prop);
        } catch (\Exception $e) {}

        return $value;

    }

    private function respondWithValidationErrorForJson($validator)
    {

        return response()->json([
            'message' => $validator->errors()->first(),
            'status' => 400
        ], 400);
    }

    private function isAcceptedPostFields($submittedFields)
    {
        $fields = [];
        $allowedFields = config('stormpath.web.register.form.fields');

        foreach($allowedFields as $key => $value) {
            //Enabled check when iOS SDK is updated to not use username in tests
//            if($value['enabled'] == false) continue;
            $fields[] = $key;
        }
        $fields[] = '_token';

        if(!empty($diff = array_diff(array_keys($submittedFields), array_values($fields)))) {
            return $diff;
        }

        return true;
    }
}
