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
use Stormpath\Resource\ResourceError;
use Event;
use Stormpath\Lumen\Exceptions\ActionAbortedException;
use Stormpath\Lumen\Events\UserHasRequestedPasswordReset;

class ForgotPasswordController extends Controller
{

    /**
     * @var Request
     */
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function postForgotPassword()
    {
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
}
