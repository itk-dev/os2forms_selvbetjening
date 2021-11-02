# selvbetjening.aarhuskommune.dk

## Getting started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes.

### Prerequisites

* [Docker](https://www.docker.com/)
* [Docker Compose](https://docs.docker.com/compose/)

### Installation

1. Clone the git repository
   ```sh
   git clone git@github.com:itk-dev/os2forms_selvbetjening selvbetjening
   ```

2. Enter the newly created project directory
   ```sh
   cd selvbetjening
   ```

3. Pull docker images and start docker containers
   ```sh
   docker-compose pull
   docker-compose up --detach

4. Install composer packages
   ```sh
   # Important: Use --no-interaction to make https://getcomposer.org/doc/06-config.md#discard-changes have effect.
   docker-compose exec phpfpm composer install --no-interaction
   ```

   **Note**: Due to <https://github.com/vaimo/composer-patches/issues/85> we use
   a composer [`post-install-cmd`
   script](https://getcomposer.org/doc/articles/scripts.md#command-events) to
   apply a patch to the OpenID Connect module (see
   [`composer.json`](composer.json) for details).

   When <https://github.com/vaimo/composer-patches/issues/85> is resolved, this
   must be added to `extra.patches` in [`composer.json`](composer.json):

   ```json
   {
       …
       "extra": {
           …
           "patches": {
               …
               "drupal/openid_connect": {
                   "Revoking group access does not reflect on applied roles (https://www.drupal.org/project/openid_connect/issues/3224128)": "https://git.drupalcode.org/project/openid_connect/-/merge_requests/31.diff"
               }
               …
           }
       }
   }
   ```

5. Install profile
   ```sh
   docker-compose exec phpfpm vendor/bin/drush site:install os2forms_forloeb_profile --existing-config
   ```

6. Download and install external libraries
   ```sh
   docker-compose exec phpfpm vendor/bin/drush webform:libraries:download
   ```

You should now be able to browse to the application

```shell
open http://$(docker-compose port nginx 80)
```

### Configuration

Some modules included in this project needs additional configuration.
Take a look at the following modules on how to configure them:

* [OS2Forms CPR Lookup](https://github.com/itk-dev/os2forms_cpr_lookup)
* [OS2Forms CVR Lookup](https://github.com/itk-dev/os2forms_cvr_lookup)
* [OS2Forms Digital Post](https://github.com/itk-dev/os2forms_digital_post)
* [OS2Forms NemLogin OpenID Connect](https://github.com/itk-dev/os2forms_nemlogin_openid_connect)

### OpenID Connect login

The [OpenID Connect module](https://www.drupal.org/project/openid_connect) is
used to authenticate users and for security reasons the module must be
configured in the `settings.local.php` file:

```php
# settings.local.php
$config['openid_connect.client.generic']['settings']['client_id'] = '…; // Get this from your IdP provider
$config['openid_connect.client.generic']['settings']['client_secret'] = '…'; // Get this from your IdP provider
$config['openid_connect.client.generic']['settings']['authorization_endpoint'] = '…'; // Get this from your OpenID Connect Discovery endpoint
$config['openid_connect.client.generic']['settings']['token_endpoint'] = '…'; // Get this from your OpenID Connect Discovery endpoint

// Set Drupal roles from map IdP roles (in the `groups` claim) on authentication.
$config['openid_connect.settings']['role_mappings']['administrator'] = ['AD-administrator'];
$config['openid_connect.settings']['role_mappings']['forloeb_designer'] = ['GG-Rolle-Digitaleworkflows-forloebsdesigner-prod'];
$config['openid_connect.settings']['role_mappings']['flow_designer'] = ['GG-Rolle-Digitaleworkflows-flowdesigner-prod'];

// Overwrite a translation to show a meaningful text on the log in button.
$settings['locale_custom_strings_en'][''] = [
   'Log in with @client_title' => 'Log in with OpenID Connect (employee)',
];

$settings['locale_custom_strings_da'][''] = [
   'Log in with @client_title' => 'Medarbejderlogin',
];
```

## Production

```sh
composer install --no-dev --optimize-autoloader
```

Install site as described above.

Apply updates by running

```sh
vendor/bin/drush --yes deploy
```
