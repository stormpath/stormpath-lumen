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

use Illuminate\Support\ServiceProvider;
use Stormpath\Client;
use Stormpath\Lumen\Commands\StormpathConfigCommand;
use Stormpath\Lumen\Http\Helpers\IdSiteModel;
use Stormpath\Lumen\Http\Helpers\PasswordPolicies;
use Stormpath\Resource\AccountStoreMapping;
use Stormpath\Stormpath;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Cookie\Middleware\EncryptCookies;

class StormpathServiceProvider extends ServiceProvider
{

    const INTEGRATION_NAME = 'stormpath-lumen';
    const INTEGRATION_VERSION = '0.1.4';

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->buildConfigFromDefaultYaml();
        $this->buildClientConfig();
        $this->buildApplicationConfig();

        $this->registerClient();
        $this->registerApplication();
        $this->registerUser();

        $this->disableCookieEncryption();

        $this->registerCommands();



    }

    public function boot()
    {
        $this->registerMiddleware();
        $this->warmResources();

        $this->checkForSocialProviders();
        $this->setPasswordPolicies();
        $this->setAccountCreationPolicy();

        $this->loadRoutes();
    }

    private function buildConfigFromDefaultYaml()
    {
        $config = dirname(__DIR__) . "/config/stormpath.yaml";

        config(['stormpath' => Yaml::parse(file_get_contents($config))]);

        if(file_exists(base_path('/stormpath.yaml'))) {
            $config = Yaml::parse(file_get_contents(base_path('/stormpath.yaml')));
            $newConfig = array_replace_recursive(config('stormpath'), $config);

            config(['stormpath'=>$newConfig]);
        }
    }

    public function buildClientConfig()
    {
        $clientConfig = [
            'client' => [
                'apiKey' => [
                    'id' => env('STORMPATH_CLIENT_APIKEY_ID') ?: null,
                    'secret' => env('STORMPATH_CLIENT_APIKEY_SECRET') ?: null
                ]
            ]
        ];

        $newConfig = array_merge(config('stormpath'), $clientConfig);
        config(['stormpath'=>$newConfig]);

        if(null === config('stormpath.client.apiKey.id') || null === config('stormpath.client.apiKey.secret')) {
            throw new \InvalidArgumentException("Please provide API Keys in your environment.  STORMPATH_CLIENT_APIKEY_ID and STORMPATH_CLIENT_APIKEY_SECRET are required.");
        }
    }

    private function buildApplicationConfig()
    {
        $applicationConfig = [
            'application' => [
                'name' => env('STORMPATH_APPLICATION_NAME') ?: null,
                'href' => env('STORMPATH_APPLICATION_HREF') ?: null
            ]
        ];

        $newConfig = array_merge(config('stormpath'), $applicationConfig);
        config(['stormpath'=>$newConfig]);

        if(null === config('stormpath.application.href')) {
            throw new \InvalidArgumentException("Please provide an Application HREF in your environment.  STORMPATH_APPLICATION_HREF is required.");
        }
    }

    private function registerClient()
    {
        $id = config( 'stormpath.client.apiKey.id' );
        $secret = config( 'stormpath.client.apiKey.secret' );

        Client::$apiKeyProperties = "apiKey.id={$id}\napiKey.secret={$secret}";
        Client::$integration = $this->buildAgent();


        $this->app->singleton('stormpath.client', function() {
            return Client::getInstance();
        });
    }

    private function registerApplication()
    {
        $this->app->singleton('stormpath.application', function() {
            $this->guardAgainstInvalidApplicationHref();
            $application = \Stormpath\Resource\Application::get(config('stormpath.application.href'));
            return $application;
        });

    }

    private function guardAgainstInvalidApplicationhref()
    {
        if (config('stormpath.application.href') == null) {
            throw new \InvalidArgumentException('Application href MUST be set.');
        }

        if (!$this->isValidApplicationHref()) {
            throw new \InvalidArgumentException(config('stormpath.application.href') . ' is not a valid Stormpath Application HREF.');
        }
    }

    private function isValidApplicationHref()
    {
        return !! strpos(config( 'stormpath.application.href' ), '/applications/');
    }

    private function buildAgent()
    {
        $agent = [];

        if(app('request')->headers->has('X-STORMPATH-AGENT')) {
            $agent[] = app('request')->header('X-STORMPATH-AGENT');
        }

        $version = $this->app->version();

        $agent[] = self::INTEGRATION_NAME . '/' . self::INTEGRATION_VERSION;
        $agent[] = 'lumen/' . $version;

        return implode(' ', $agent);
    }

    private function registerUser()
    {
        $this->app->bind('stormpath.user', function($app) {

            try {
                $spApplication = app('stormpath.application');
            } catch (\Exception $e) {
                return null;
            }

            $cookie = $app->request->cookie(config('stormpath.web.accessTokenCookie.name'));

            if(null === $cookie) {
                $cookie = $this->refreshCookie($app->request);
            }

            try {
                if($cookie instanceof \Symfony\Component\HttpFoundation\Cookie) {
                    $cookie = $cookie->getValue();
                }
                $result = (new \Stormpath\Oauth\VerifyAccessToken($spApplication))->verify($cookie);
                return $result->getAccount();
            } catch (\Exception $e) {}

            return null;

        });
    }

    private function disableCookieEncryption()
    {
        $this->app->resolving(EncryptCookies::class, function ($object) {
            $object->disableFor(config('stormpath.web.accessTokenCookie.name'));
            $object->disableFor(config('stormpath.web.refreshTokenCookie.name'));
        });
    }

    private function warmResources()
    {
        if(config('stormpath.application.href') == null)  return;
        $cache = $this->app['cache.store'];

        if($cache->has('stormpath.resourcesWarm') && $cache->get('stormpath.resourcesWarm') == true) return;

        app('stormpath.client');
        $application = app('stormpath.application');

        $dasm = AccountStoreMapping::get($application->defaultAccountStoreMapping->href);

        $mappings = $application->getAccountStoreMappings(['expand'=>'accountStore']);
        $accountStoreArray = [];
        
        foreach($mappings as $mapping) {
            $accountStoreArrayValues = [
                'href' => $mapping->accountStore->href,
                'name' => $mapping->accountStore->name
            ];
            if(isset($mapping->accountStore->provider)) {
                $accountStoreArrayValues['provider'] = [
                    'href' => $mapping->accountStore->provider->href,
                    'providerId' => $mapping->accountStore->provider->providerId
                ];
            }
            $accountStoreArray[] = $accountStoreArrayValues;
        }


        $asm = AccountStoreMapping::get($application->accountStoreMappings->href,['expand'=>'accountStore']);

        $passwordPolicy = $dasm->getAccountStore()->getProperty('passwordPolicy');

        $accountCreationPolicy = $dasm->getAccountStore(['expand'=>'accountCreationPolicy'])->accountCreationPolicy;

        $passwordPolicies = PasswordPolicies::get($passwordPolicy->href);


        $cache->rememberForever('stormpath.defaultAccountStoreMapping', function() use ($dasm) {
            return $dasm;
        });

        $cache->rememberForever('stormpath.accountStoreMappings', function() use ($asm) {
            return $asm;
        });

        $cache->rememberForever('stormpath.accountStores', function() use ($accountStoreArray) {
            return $accountStoreArray;
        });

        $cache->rememberForever('stormpath.passwordPolicy', function() use ($passwordPolicy) {
            return $passwordPolicy;
        });

        $cache->rememberForever('stormpath.accountCreationPolicy', function() use ($accountCreationPolicy) {
            return $accountCreationPolicy;
        });

        $cache->rememberForever('stormpath.passwordPolicies', function() use ($passwordPolicies) {
            return $passwordPolicies;
        });

        $cache->rememberForever('stormpath.resourcesWarm', function() {
            return true;
        });
    }

    private function checkForSocialProviders()
    {
        if(config('stormpath.application.href') == null)  return;

        $model = app('cache.store')->rememberForever('stormpath.idsitemodel', function() {
            $idSiteModel = $this->getIdSiteModel();
            return IdSiteModel::get($idSiteModel->href);
        });

        $providers = $model->getProperty('providers');


        foreach($providers as $provider) {
            config(['stormpath.web.social.enabled' => true]);

            switch ($provider->providerId) {
                case 'facebook' :
                    $this->setupFacebookProvider($provider);
                    break;
                case 'google' :
                    $this->setupGoogleProvider($provider);
                    break;
                case 'linkedin' :
                    $this->setupLinkedinProvider($provider);
                    break;
            }
        }
    }

    private function getIdSiteModel()
    {
        $model = app('stormpath.application')->getProperty('idSiteModel');

        if($model == null) {
            throw new \InvalidArgumentException('ID Site could not initialize, please visit ID Site from the Stormpath Dashboard and then clear your cache');
        }

        return $model;

    }

    private function setupFacebookProvider($provider)
    {
        config(['stormpath.web.social.facebook.enabled' => true]);
        config(['stormpath.web.social.facebook.name' => 'Facebook']);
        config(['stormpath.web.social.facebook.clientId' => $provider->clientId]);
    }

    private function setupGoogleProvider($provider)
    {
        config(['stormpath.web.social.google.enabled' => true]);
        config(['stormpath.web.social.google.name' => 'Google']);
        config(['stormpath.web.social.google.clientId' => $provider->clientId]);
        config(['stormpath.web.social.google.callbackUri' => $provider->redirectUri]);
    }

    private function setupLinkedinProvider($provider)
    {
        config(['stormpath.web.social.linkedin.enabled' => true]);
        config(['stormpath.web.social.linkedin.name' => 'LinkedIn']);
        config(['stormpath.web.social.linkedin.clientId' => $provider->clientId]);

    }

    private function setPasswordPolicies()
    {
        if(config('stormpath.web.forgotPassword.enabled') == true) return;

        if(config('stormpath.web.changePassword.enabled') == true) return;

        if(config('stormpath.application.href') == null)  return;

        config(['stormpath.web.forgotPassword.enabled' => false]);
        config(['stormpath.web.changePassword.enabled' => false]);

        $cache = $this->app['cache.store'];

        $passwordPolicies = $cache->get('stormpath.passwordPolicies');

        if($passwordPolicies->getProperty('resetEmailStatus') == Stormpath::ENABLED) {
            config(['stormpath.web.forgotPassword.enabled' => true]);
            config(['stormpath.web.changePassword.enabled' => true]);
            return;
        }
    }

    private function setAccountCreationPolicy()
    {
        if(config('stormpath.web.verifyEmail.enabled') == true) return;

        $cache = $this->app['cache.store'];

        if(!$cache->has('stormpath.accountCreationPolicy')) {
            $this->warmResources();
        }

        config(['stormpath.web.verifyEmail.enabled' => false]);

        $accountCreationPolicy = $cache->get('stormpath.accountCreationPolicy');

        if($accountCreationPolicy == null) {
            return;
        }

        if($accountCreationPolicy->verificationEmailStatus == Stormpath::ENABLED) {
            config(['stormpath.web.verifyEmail.enabled' => true]);
        }

        if(config('stormpath.web.verifyEmail.enabled') && config('stormpath.web.register.autoLogin')) {
            throw new \InvalidArgumentException('AutoLogin and Verify are both enabled.  Either disable Auto Login, or turn off verification email.');
        }
    }

    private function loadRoutes()
    {
        require __DIR__ . '/../Http/routes.php';

        if(config('stormpath.web.social.enabled')) {
            require __DIR__ . '/../Http/socialRoutes.php';
        }
    }


    private function registerCommands()
    {
        $this->app->singleton('stormpath.config.command', function()
        {
            return new StormpathConfigCommand();
        });

        $this->commands(
            'stormpath.config.command'
        );
    }

    private function registerMiddleware()
    {
        $this->app->routeMiddleware(['stormpath.auth' => \Stormpath\Lumen\Http\Middleware\Authenticate::class]);
        $this->app->routeMiddleware(['stormpath.guest' => \Stormpath\Lumen\Http\Middleware\RedirectIfAuthenticated::class]);

    }


}