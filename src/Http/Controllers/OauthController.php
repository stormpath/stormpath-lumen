<?php
/*
 * Copyright 2016 Stormpath, Inc.
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
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller;
use Stormpath\Authc\Api\OAuthClientCredentialsRequestAuthenticator;
use Stormpath\Authc\Api\OAuthRequestAuthenticator;
use Stormpath\Lumen\Support\Oauth;
use Stormpath\Oauth\OauthGrantAuthenticationResult;
use Stormpath\Stormpath;

class OauthController extends Controller
{

    public function getTokens(Request $request)
    {
        $grantType = $this->getGrantType($request);

        switch($grantType) {
            case 'password' :
                return $this->doPasswordGrantType($request);
            // @codeCoverageIgnoreStart
            case 'client_credentials' :
                return $this->doClientCredentialsGrantType($request);
            // @codeCoverageIgnoreEnd
            case 'refresh_token' :
                return $this->doRefreshGrantType($request);
            default :
                return $this->respondUnsupportedGrantType();
        }

    }

    /** @codeCoverageIgnore */
    private function doClientCredentialsGrantType($request)
    {
        if(!config('stormpath.web.oauth2.client_credentials.enabled')) {
            return $this->respondUnsupportedGrantType();
        }

        if(!$this->hasAuthorizationHeader($request)) {
            return $this->respondWithInvalidRequest('You must supply Basic Authorization Header');
        }

        $token = base64_decode($this->basicToken($request));

        if('' == $token) {
            return $this->respondWithInvalidRequest('The authorization header is in an invalid form');
        }

        list($id, $secret) = explode(':',$token);


        $oauth = new Oauth();
        $oauth->clientId = $id;
        $oauth->clientSecret = $secret;
        $oauth->grantType = 'client_credentials';

        $response = app('stormpath.client')->getDataStore()->create(app('stormpath.application')->href . '/oauth/token', $oauth, \Stormpath\Lumen\Support\OauthResponse::class);

        return response()->json([
            'access_token' => $response->accessToken,
            'token_type' => $response->tokenType,
            'expires_in' => $response->expiresIn,
            'stormpath_access_token_href' => $response->accessTokenHref
        ]);


    }

    private function doPasswordGrantType($request)
    {
        try {
            $passwordGrant = new \Stormpath\Oauth\PasswordGrantRequest($request->input('username'), $request->input('password'));
            $auth = new \Stormpath\Oauth\PasswordGrantAuthenticator(app('stormpath.application'));
            $result = $auth->authenticate($passwordGrant);
            return $this->respondWithAccessTokens($result);
        } catch (\Exception $e) {
            return $this->respondWithInvalidLogin($e);
        }
    }

    private function respondUnsupportedGrantType()
    {
        return response()->json([
            'message' => 'The authorization grant type is not supported by the authorization server.',
            'error' => 'unsupported_grant_type'
        ], 400);
    }

    private function getGrantType($request)
    {
        return $request->input('grant_type');
    }

    private function respondWithInvalidLogin($e)
    {
        return response()->json([
            'message' => $e->getMessage(),
            'error' => 'invalid_grant'
        ], 400);
    }

    private function respondWithAccessTokens(OauthGrantAuthenticationResult $result)
    {
        return response()->json([
            'access_token' => $result->getAccessTokenString(),
            'expires_in' => $result->getExpiresIn(),
            'refresh_token' => $result->getRefreshTokenString(),
            'token_type' => 'Bearer'
        ]);
    }

    private function doRefreshGrantType($request)
    {
        if(null === $request->input('refresh_token')) {
            return $this->respondWithInvalidRequest('The refresh_token parameter is required.');
        }

        try {
            $refreshGrant = new \Stormpath\Oauth\RefreshGrantRequest($request->input('refresh_token'));

            $auth = new \Stormpath\Oauth\RefreshGrantAuthenticator(app('stormpath.application'));
            $result = $auth->authenticate($refreshGrant);
            return $this->respondWithAccessTokens($result);
        } catch (\Exception $e) {
            return $this->respondWithInvalidLogin($e);
        }

    }

    private function respondWithInvalidRequest($message = 'Invalid Request')
    {
        return response()->json([
            'message' => $message,
            'error' => 'invalid_request'
        ], 400);
    }


    private function hasAuthorizationHeader(Request $request)
    {
        return null !== $request->header('Authorization');
    }

    /**
     * Get the basic token from the request headers.
     *
     * @return string|null
     */
    public function basicToken(Request $request)
    {
        $header = $request->header('Authorization', '');

        if (Str::startsWith($header, 'Basic ')) {
            return Str::substr($header, 6);
        }
    }

}