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

8. **Login**

  To access a protected route, the user must first login.

  You can login by visiting the `/login` URL and submitting the login form.


9. **Register**

  To be able to login, your users first need an account.

  Users can register by visiting the `/register` URL and submitting the
  registration form.

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
