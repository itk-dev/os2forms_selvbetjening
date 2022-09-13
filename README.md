# selvbetjening.aarhuskommune.dk

## Getting started

These instructions will get you a copy of the project up and running on your
local machine for development and testing purposes.

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
   ```

4. Install composer packages

   ```sh
   # Important: Use --no-interaction to make https://getcomposer.org/doc/06-config.md#discard-changes have effect.
   docker-compose exec phpfpm composer install --no-interaction
   ```

5. Install profile

   ```sh
   docker-compose exec phpfpm vendor/bin/drush site:install os2forms_forloeb_profile --existing-config
   ```

   Should you encounter the following error:

   ```sh
   In EntityStorageBase.php line 557:
   "config_entity_revisions_type" entity with ID 'webform_revisions' already exists.
   ```

   Proceed to remove this entry from the db via the sql cli:

   ```sh
   docker-compose exec phpfpm vendor/bin/drush sql:query 'DELETE FROM config WHERE name="config_entity_revisions.config_entity_revisions_type.webform_revisions";'
   ```

   Afterwards, run config-import to import config from files:

   ```sh
   docker-compose exec phpfpm vendor/bin/drush config:import
   ```

6. Download and install external libraries

   ```sh
   docker-compose exec phpfpm vendor/bin/drush webform:libraries:download
   ```

You should now be able to browse to the application

```sh
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

### Selvbetjening Module

The `OS2Forms Selvbetjening` module updates the Webform Email Handler
by adding a description to the message body section. The
description should be configured in the `settings.local.php` file:

```php
$config['os2forms_selvbetjening']['email_body_description'] = 'Brug enten standardsvaret eller definer dit eget svar. Se <a href="https://os2forms.os2.eu/mail-tekster">OS2Forms Loop</a> for andre standarder og eksempler.';
```

If it is not set, no description is added.

### Maestro

We use the [Maestro module](https://www.drupal.org/project/maestro) to make workflows.

To avoid having to run the
[Orchestrator](https://www.drupal.org/docs/contributed-modules/maestro/installation#s-maestro-engine-also-know-as-the-orchestrator)
manually, a token must be set in
`/admin/config/workflow/maestro`. The Orchestrator can then be run by visiting
`https://[site]/orchestrator/{token}`.
Adding the following cronjob to your crontab will run
the Orchestrator every five minutes.

```cron
*/5 * * * * /usr/bin/curl --location https://[site]/orchestrator/{token} > /dev/null 2>&1; /usr/local/bin/cron-exit-status -c 'Some exit message probably containing [site]' -v $?
```

In `/admin/config/workflow/maestro` you can also configure
whether a refresh of the Maestro Task Console should run the Orchestrator,
which certainly could be an advantage during tests.

### REST API

We use [Webform REST](https://www.drupal.org/project/webform_rest) to expose a
number of API endpoints. See [OS2Forms REST
API](web/modules/custom/os2forms_rest_api/README.md) for details.

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

The database of production must never be copied to a local development
environment, as its data contains personal data.

If developers need an actual database for local development, the stg-environment
can be made ready for download by ensuring that you delete all submissions and
other informations that can have personal character, before downloading.
