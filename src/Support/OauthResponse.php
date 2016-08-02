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

namespace Stormpath\Lumen\Support;

use Stormpath\Authc\Api\OAuthAuthenticationResult;
use Stormpath\Resource\Resource;

class OauthResponse extends Resource
{
    /**
     * Gets the accessToken property
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->getProperty('access_token');
    }

    /**
     * Gets the tokenType property
     *
     * @return string
     */
    public function getTokenType()
    {
        return $this->getProperty('token_type');
    }
    
    /**
     * Gets the expiresIn property
     *
     * @return integer
     */
    public function getExpiresIn()
    {
        return $this->getProperty('expires_in');
    } 
    
    /**
     * Gets the accessTokenHref property
     *
     * @return string
     */
    public function getAccessTokenHref()
    {
        return $this->getProperty('stormpath_access_token_href');
    } 
    
    
    




}