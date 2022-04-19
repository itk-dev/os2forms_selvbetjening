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

### GetOrganized

To use the custom GetOrganized module the module must be
configured in the `settings.local.php` file:
```php
# settings.local.php
$config['os2forms_get_organized'] = [
  'username' => '…',
  'password' => '…',
  'base_url' => '…',
];
```

### REST API

```sh
https://127.0.0.1:8000/da/webform_rest/ansoegning_om_hjemmearbejde_amr_/fields
```

#### Authentication

We use [Key auth](https://www.drupal.org/project/key_auth) for authenticating
api users.

A user can access the REST API if

1. it has the “API user” (`api_user`) role and
2. has a generated key (User > Edit > Key authentication; `/user/«user
   id»/key-auth`).

The “API user” role gives read-only access to the API. To get read access, a
user must also have the “API user (write)” (`api_user_write`) role.

### Endpoints

| Name               | Path                                           | Methods |
|--------------------|------------------------------------------------|---------|
| Webform Elements   | `/webform_rest/{webform_id}/elements`          | GET     |
| Webform Fields     | `/webform_rest/{webform_id}/fields`            | GET     |
| Webform Submission | `/webform_rest/{webform_id}/submission/{uuid}` | GET     |
| Webform Submit     | `/webform_rest/submit`                         | POST    |

### Examples

Request:

```sh
> curl --silent --location --header 'api-key: …' --header 'content-type: application/json' https://127.0.0.1:8000/webform_rest/submit --data @- <<'JSON'
{
  "webform_id": "{webform_id}",
  "//": "Webform field values (cf. /webform_rest/{webform_id}/fields)",
  "navn_": "Mikkel",
  "adresse": "Livets landevej",
  "mail_": "mikkel@example.com",
  "telefonnummer_": "12345678"
}
JSON
```

Response:

```json
{"sid":"6d95afe9-18d1-4a7d-a1bf-fd38c58c7733"}
```

(the `sid`value is a webform submission uuid).

## Production

```sh
composer install --no-dev --optimize-autoloader
```

Install site as described above.

Apply updates by running

```sh
vendor/bin/drush --yes deploy
```

Configure the [`memcache` module](https://www.drupal.org/project/memcache):
<https://git.drupalcode.org/project/memcache/blob/8.x-2.x/README.txt>


## Production Database
The database of production must never be copied to a local development environment, as its data contains personal data.

If developers need an actual database for local development, the stg-environment can be made ready for download by ensuring that you delete all submissions and other informations that can have personal character, before downloading.
