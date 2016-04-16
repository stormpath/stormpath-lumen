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
use Illuminate\Routing\Controller;
use Stormpath\Resource\ResourceError;
use Event;
use Stormpath\Lumen\Exceptions\ActionAbortedException;
use Stormpath\Lumen\Events\UserHasRequestedPasswordReset;

class VerifyEmailController extends Controller
{

    /**
     * @var Request
     */
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getVerifyEmail()
    {
        $token = urldecode($this->request->input('spToken'));
        if(null === $token || '' == $token) {
            return $this->respondWithError('sptoken parameter not provided.', 400);
        }

        $client = app('stormpath.client');

        try {
            $client->verifyEmailToken($token);

            return response()->json();

        } catch (\Stormpath\Resource\ResourceError $re) {
            return $this->respondWithError('Could not verify your email.  Please request a new token.', $re->getStatus());
        }
    }

    public function postVerifyEmail()
    {
        $email = $this->request->input('email');
        if(null === $email || '' == $email) {
            return $this->respondWithError('email parameter not provided.', 400);
        }

        $application = app('stormpath.application');

        try {
            $application->sendVerificationEmail($email);
        } catch (\Stormpath\Resource\ResourceError $re) {}

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
