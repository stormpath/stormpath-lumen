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
use Stormpath\Lumen\Http\Traits\AuthenticatesUser;
use Illuminate\Validation\Factory as Validator;
use Event;
use Stormpath\Lumen\Events\UserHasResetPassword;

class ChangePasswordController extends Controller
{

    use AuthenticatesUser;

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
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->validator = app('validator');
    }

    public function postChangePassword()
    {
        $newPassword = $this->request->input('password');
        $token = urldecode($this->request->input('spToken'));

        $validator = $this->changePasswordValidator();

        if($validator->fails()) {
            return $this->respondWithError('Validation Failed', 400, ['validatonErrors' => $validator->errors()]);
        }

        $application = app('stormpath.application');

        try {
            $application->resetPassword($token, $newPassword);

            return $this->respondOk();

        } catch (\Stormpath\Resource\ResourceError $re) {
            return $this->respondWithError($re->getMessage(), $re->getStatus());
        }
    }

    private function changePasswordValidator()
    {
        $validator = $this->validator->make(
            $this->request->all(),
            [
                'password' => 'required'
            ],
            [
                'password.required' => 'Password is required.'
            ]
        );


        return $validator;
    }

    private function respondOk()
    {
        return response()->json();
    }

    private function respondWithError($message, $statusCode = 400, $extra = [])
    {
        $error = [
            'errors' => [
                'message' => $message
            ]
        ];

        if(!empty($extra)) {
            $error['errors'] = array_merge($error['errors'], $extra);
        }
        return response()->json($error, $statusCode);
    }

}
