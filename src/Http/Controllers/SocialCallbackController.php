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

use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Stormpath\Lumen\Exceptions\SocialLoginException;
use Stormpath\Lumen\Http\Helpers\IdSiteSessionHelper;
use Stormpath\Lumen\Http\Traits\Cookies;
use Stormpath\Provider\FacebookProviderAccountRequest;
use Stormpath\Provider\GithubProviderAccountRequest;
use Stormpath\Provider\LinkedInProviderAccountRequest;
use Stormpath\Provider\ProviderAccountRequest;
use Stormpath\Resource\AccessToken;
use Stormpath\Resource\Account;

/** @codeCoverageIgnore */
class SocialCallbackController extends Controller
{
    use Cookies;

    private $application;

    public function __construct($application = null)
    {
        $this->application = $application;

        app('cache.store')->forget('stormpath.application');

        if(null === $this->application) {
            $this->application = app('stormpath.application');
        }

    }

    public function facebook(Request $request)
    {
        try {

            $providerAccountRequest = new FacebookProviderAccountRequest($this->buildProviderAccountRequestArray($request));

            $account = $this->sendProviderAccountRequest($providerAccountRequest);

            return $this->respondWithAccount($account);

        } catch (\Stormpath\Resource\ResourceError $re) {
            return $this->respondWithError($re);
        } catch (SocialLoginException $e) {
            return $this->respondWithError($e);
        }

    }

    public function google(Request $request)
    {
        try {
            $providerAccountRequest = new \Stormpath\Provider\GoogleProviderAccountRequest($this->buildProviderAccountRequestArray($request));

            $account = $this->sendProviderAccountRequest($providerAccountRequest);

            return $this->respondWithAccount($account);

        } catch (\Stormpath\Resource\ResourceError $re) {
            return $this->respondWithError($re);
        } catch (SocialLoginException $e) {
            return $this->respondWithError($e);
        }

    }

    public function github(Request $request)
    {
        try {

            $providerAccountRequest = new GithubProviderAccountRequest($this->buildProviderAccountRequestArray($request));

            $account = $this->sendProviderAccountRequest($providerAccountRequest);

            return $this->respondWithAccount($account);

        } catch (\Stormpath\Resource\ResourceError $re) {
            return $this->respondWithError($re);
        } catch (SocialLoginException $e) {
            return $this->respondWithError($e);
        }

    }

    public function linkedin(Request $request)
    {
         try {

            $providerAccountRequest = new LinkedInProviderAccountRequest($this->buildProviderAccountRequestArray($request));

            $account = $this->sendProviderAccountRequest($providerAccountRequest);

             return $this->respondWithAccount($account);

         } catch (\Stormpath\Resource\ResourceError $re) {
             return $this->respondWithError($re);
         } catch (SocialLoginException $e) {
             return $this->respondWithError($e);
         }

    }


    protected function sendProviderAccountRequest(ProviderAccountRequest $providerAccountRequest)
    {
        $result = $this->application->getAccount($providerAccountRequest);
        return $result->account;
    }

    protected function getCookies(Account $account)
    {
        $idSiteSession = new IdSiteSessionHelper();
        $accessTokens = $idSiteSession->create($account);
        $tokens = [];
        $tokens['access'] = $this->makeAccessTokenCookie($accessTokens->getProperty('access_token'));
        $tokens['refresh'] = $this->makeRefreshTokenCookie($accessTokens->getProperty('refresh_token'));
        return $tokens;
    }

    private function respondWithAccount(Account $account)
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

        $tokens = $this->getCookies($account);

        return response()->json($properties)->cookie($tokens['access'])->cookie($tokens['refresh']);
    }

    private function getPropertyValue($account, $prop)
    {
        $value = null;
        try {
            $value = $account->getProperty($prop);
        } catch (\Exception $e) {
            return null;
        }

        return $value;

    }

    private function respondWithError($exception)
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'status' => $exception->getStatus()
        ], $exception->getStatus());
    }

    private function buildProviderAccountRequestArray($request)
    {
        $array = [];

        if($request->has('code')) {
            $array['code'] = $request->get('code');
        }

        if($request->has('access_token')) {
            $array['accessToken'] = $request->get('access_token');
        }

        if($request->has('providerData')) {
            if(!empty($request->get('providerData')['accessToken'])) {
                $array['accessToken'] = $request->get('providerData')['accessToken'];
            }
        }

        return $array;
    }

}
