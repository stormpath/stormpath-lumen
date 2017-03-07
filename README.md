#Stormpath is Joining Okta
We are incredibly excited to announce that [Stormpath is joining forces with Okta](https://stormpath.com/blog/stormpaths-new-path?utm_source=github&utm_medium=readme&utm-campaign=okta-announcement). Please visit [the Migration FAQs](https://stormpath.com/oktaplusstormpath?utm_source=github&utm_medium=readme&utm-campaign=okta-announcement) for a detailed look at what this means for Stormpath users.

We're available to answer all questions at [support@stormpath.com](mailto:support@stormpath.com).

[![Latest Stable Version](https://poser.pugx.org/stormpath/lumen/v/stable.svg)](https://packagist.org/packages/stormpath/lumen)
[![Latest Unstable Version](https://poser.pugx.org/stormpath/lumen/v/unstable.svg)](https://packagist.org/packages/stormpath/lumen)
[![License](https://poser.pugx.org/stormpath/lumen/license.svg)](https://packagist.org/packages/stormpath/lumen)
[![Chat](https://img.shields.io/badge/chat-on%20freenode%20-green.svg)](http://webchat.freenode.net/?channels=#stormpath)
[![Chat](https://img.shields.io/badge/support-support@stormpath.com-blue.svg)](mailto:support@stormpath.com?subject=Stormpath+Lumen+Integration)


## Getting Started

Follow these steps to add Stormpath user authentication to your Lumen app.

1. **Download Your Key File**

  [Download your key file](https://support.stormpath.com/hc/en-us/articles/203697276-Where-do-I-find-my-API-key-) from the Stormpath Console.

2. **Store Your Key As Environment Variables**

  Open your key file and grab the **API Key ID** and **API Key Secret**, then add this to your `.env` file in the root of your project:

  > You may need to create a `.env` file if this is a fresh install of lumen.

  ```php
  STORMPATH_CLIENT_APIKEY_ID=<YOUR-ID-HERE>
  STORMPATH_CLIENT_APIKEY_SECRET=<YOUR-SECRET-HERE>
  ```

3. **Get Your Stormpath Application HREF**

  Login to the [Stormpath Console](https://api.stormpath.com/) and grab the *HREF* (called **REST URL** in the UI) of your *Application*. It should look something like this:

  `https://api.stormpath.com/v1/applications/q42unYAj6PDLxth9xKXdL`

4. **Store Your Stormpath App HREF In the `.env` file**

  ```php
  STORMPATH_APPLICATION_HREF=<YOUR-STORMPATH-APP-HREF>
  ```

5. **Install The Package**
    
  Open your composer.json file and add the following to your require block:
  
  ```bash
  "stormpath/lumen": "^0.1"
  ```

6. **Include It In Your App**

   Open you `bootstrap/app.php` file and add the following to your providers section

  ```php
  $app->register(\Stormpath\Lumen\Support\StormpathServiceProvider::class);
  ```

7. **Configure It**

  To modify the configuration of the package, you will need to publish the config file. Run the following in your terminal:

  ```bash
  $ php artisan stormpath:config
  ```

  This will create a `stormpath.yaml` file in the root of your project with all the options you are able to modify.  By default,
  Login, Logout, OAuth, and Register routes will be enabled.  Other routes will be enabled based on your directory settings.

8. **Login**

  Working with an API, we suggest that you work with OAuth tokens.  We have created a route for your, `/oauth/tokens` where
  you can do `client_credentials`, `password`, or `refresh` grant types.

  * **Client Credentials**

  In this workflow, an api key and secret is provisioned for a stormpath account. These credentials can be exchanged for
  an access token by making a POST request to `/oauth/token` on the web application. The request must look like this:

  ```
  POST /oauth/token
  Authorization: Basic <base64UrlEncoded(apiKeyId:apiKeySecret)>

  grant_type=client_credentials
  ```

  * **Password Grant**

  In this workflow, an account can post their login (username or email) and password to the ``/oauth/token` endpoint,
  with the following body data:

  ```
  POST /oauth/token

  grant_type=password
  &username=<username>
  &password=<password>
  ```

  * **Refresh Grant**

  The refresh grant type is required for clients using the password grant type to refresh their access_token.
  Thus, it's automatically enabled alongside the password grant type.

  An account can post their refresh_token with the following body data:

  ```
  POST /oauth/token
  grant_type=refresh_token&
  refresh_token=<refresh token>
  ```

  The product guide for token management: http://docs.stormpath.com/guides/token-management

9. **Register**

   To get the model for the registration form, make a `GET` request to `/register`.  This will return a JSON representation
   of the form along with the available Account Stores.

   ```
   {
     "form": {
       "fields": [
         {
           "enabled": true,
           "label": "First Name",
           "placeholder": "First Name",
           "required": true,
           "type": "text"
         },
         {
           "enabled": true,
           "label": "Last Name",
           "placeholder": "Last Name",
           "required": true,
           "type": "text"
         },
         ...
       ]
     },
     "accountStores": [
       {
         "href": "https://api.stormpath.com/v1/directories/6t1orcyGhqLvObgvsohdYu",
         "name": "Test Directory",
         "provider": {
           "href": "https://api.stormpath.com/v1/directories/6t1orcyGhqLvObgvsohdYu/provider",
           "providerId": "stormpath"
         }
       }
     ]
   }
   ```

   When you want to register a new Account, take the user data from the form model and put into the
   body of a `POST` request to the `/register` endpoint.

10. **That's It!**

  You just added user authentication to your app with Stormpath. 



## Support
If you are having issues with this package, please feel free to submit an issue on this github repository.  If it is
an issue you are having that needs a little more private attention, please feel free to contact us at
[support@stormpath.com](mailto:support@stormpath.com?subject=Stormpath+Lumen+Integration) or visit our
[support center](https://support.stormpath.com).

## Contributing
We welcome anyone to make contributions to this project. Just fork the `develop` branch of this repository, make your
changes, then issue a pull request on the `develop` branch.

Any pull request you make will need to have associated tests with them.  If a test is not provided, the pull request
will be closed automatically.  Also, any pull requests made to a branch other than `develop` will be closed and a
new submission will need to be made to the `develop` branch.

We regularly maintain this repository, and are quick to review pull requests and accept changes!

## Copyright

Copyright &copy; 2013-2016 Stormpath, Inc. and contributors.

This project is open-source via the [Apache 2.0 License](http://www.apache.org/licenses/LICENSE-2.0).


[documentation]: https://docs.stormpath.com/php/laravel/latest/
