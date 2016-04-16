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
/*
 |--------------------------------------------------------------------------
 | Social Callback Routes
 |--------------------------------------------------------------------------
 */
if (config('stormpath.web.social.enabled')) {
    if (config('stormpath.web.social.facebook.enabled')) {
        $this->app->get(config('stormpath.web.social.facebook.uri'), ['as' => 'stormpath.callbacks.facebook', 'uses' => 'Stormpath\Lumen\Http\Controllers\SocialCallbackController@facebook']);
    }

    if (config('stormpath.web.social.google.enabled')) {
        $this->app->get(config('stormpath.web.social.google.uri'), ['as' => 'stormpath.callbacks.google', 'uses' => 'Stormpath\Lumen\Http\Controllers\SocialCallbackController@google']);
    }

    if (config('stormpath.web.social.github.enabled')) {
        $this->app->get(config('stormpath.web.social.github.uri'), ['as' => 'stormpath.callbacks.github', 'uses' => 'Stormpath\Lumen\Http\Controllers\SocialCallbackController@github']);
    }

    if (config('stormpath.web.social.linkedin.enabled')) {
        $this->app->get(config('stormpath.web.social.linkedin.uri'), ['as' => 'stormpath.callbacks.linkedin', 'uses' => 'Stormpath\Lumen\Http\Controllers\SocialCallbackController@linkedin']);
    }

}