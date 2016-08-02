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

use Stormpath\Resource\InstanceResource;

class Oauth extends InstanceResource
{
    /**
     * Sets the clientId property
     *
     * @param string $clientId The clientId of the object
     * @return self
     */
    public function setClientId($clientId)
    {
        $this->setProperty('client_id', $clientId);

        return $this;
    }

    /**
     * Gets the clientId property
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->getProperty('client_id');
    }



    /**
     * Sets the clientSecret property
     *
     * @param string $clientSecret The clientSecret of the object
     * @return self
     */
    public function setClientSecret($clientSecret)
    {
        $this->setProperty('client_secret', $clientSecret);

        return $this;
    }

    /**
     * Gets the clientSecret property
     *
     * @return string
     */
    public function getClientSecret()
    {
        return $this->getProperty('client_secret');
    }


    /**
     * Sets the grantType property
     *
     * @param string $grantType The grantType of the object
     * @return self
     */
    public function setGrantType($grantType)
    {
        $this->setProperty('grant_type', $grantType);

        return $this;
    }

    /**
     * Gets the grantType property
     *
     * @return string
     */
    public function getGrantType()
    {
        return $this->getProperty('grant_type');
    }








}