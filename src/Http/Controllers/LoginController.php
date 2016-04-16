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

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory as Validator;
use Stormpath\Lumen\Http\Traits\AuthenticatesUser;
use Stormpath\Lumen\Http\Traits\Cookies;
use Stormpath\Resource\Account;
use Symfony\Component\HttpFoundation\Cookie;

class LoginController extends Controller
{

    use AuthenticatesUser, Cookies;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var
     */
    private $validator;


    /**
     * LoginController constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->validator = app('validator');
    }

    public function getLogin()
    {
        return $this->respondWithForm();
    }

    public function postLogin()
    {

        if($this->isSocialLoginAttempt()) {
            return $this->doSocialLogin();
        }

        $validator = $this->loginValidator();

        if($validator->fails()) {
            return $this->respondWithValidationErrorForJson($validator);
        }

        try {

            $result = $this->authenticate($this->request->input('login'), $this->request->input('password'));
            $accessTokenCookie = $this->makeAccessTokenCookie($result->getAccessTokenString());
            $refreshTokenCookie = $this->makeRefreshTokenCookie($result->getRefreshTokenString());

            $account = $result->getAccessToken()->getAccount();

            return $this->respondWithAccount($account, $accessTokenCookie, $refreshTokenCookie);

        } catch (\Stormpath\Resource\ResourceError $re) {
            return $this->respondWithError($re->getMessage(), $re->getStatus());
        }
    }

    public function getLogout()
    {
        return response()
            ->json()
            ->cookie(
                new Cookie(config('stormpath.web.accessTokenCookie.name'), null, 0)
            )
            ->cookie(
                new Cookie(config('stormpath.web.refreshTokenCookie.name'), null, 0)
            );
    }

    private function loginValidator()
    {
        $validator = $this->validator->make(
            $this->request->all(),
            [
                'login' => 'required',
                'password' => 'required'
            ],
            [
                'login.required' => 'Login is required.',
                'password.required' => 'Password is required.'
            ]
        );


        return $validator;
    }

    private function respondWithForm()
    {

        $data = [
            'form' => [
                'fields' => [
                    [
                        'label' => 'Username or Email',
                        'name' => 'login',
                        'placeholder' => 'Username or Email',
                        'required' => true,
                        'type' => 'text'
                    ],
                    [
                        'label' => 'Password',
                        'name' => 'password',
                        'placeholder' => 'Password',
                        'required' => true,
                        'type' => 'password'
                    ]
                ]
            ],
            'accountStores' => [
                app('cache.store')->get('stormpath.accountStores')
            ],

        ];
        return response($data)->header('Content-Type', 'application/json');

    }

    private function respondWithError($message, $statusCode = 400)
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

    private function isSocialLoginAttempt()
    {
        $attempt = $this->request->has('providerData');
        if(!$attempt) {
            return false;
        }
        switch ($provider = $this->request->input('providerData')['providerId'])
        {
            /** @codeCoverageIgnoreStart */
            case 'google' :
            case 'facebook' :
            case 'linkedin' :
                return true;
            /** @codeCoverageIgnoreEnd */
            case 'stormpath' :
                throw new \InvalidArgumentException("Please use the standard login/password method instead");
            default :
                throw new \InvalidArgumentException("The social provider {$provider} is not supported");
        }
    }
    /** @codeCoverageIgnore */
    private function doSocialLogin()
    {
        switch ($provider = $this->request->input('providerData')['providerId'])
        {
            case 'google' :
                return app(SocialCallbackController::class)->google($this->request);
            case 'facebook' :
                return app(SocialCallbackController::class)->facebook($this->request);
            case 'linkedin' :
                return app(SocialCallbackController::class)->linkedin($this->request);
        }
    }

}
