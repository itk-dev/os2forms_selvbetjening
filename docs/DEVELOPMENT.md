# Testing OpenID Connect

We use [OpenId Connect Server Mock](https://github.com/Soluto/oidc-server-mock).

**Note**: The following assumes that [the itkdev-docker-compose helper
script](https://github.com/itk-dev/devops_itkdev-docker#helper-scripts) is used
for development.

Configure [the OpenID Connect
module](https://www.drupal.org/project/openid_connect) to use
<http://idp.selvbetjening.local.itkdev.dk> as identity provider (cf. [the
discovery
document](http://idp.selvbetjening.local.itkdev.dk/.well-known/openid-configuration)):

```php
# web/sites/default/settings.local.php
…

// http://idp.selvbetjening.local.itkdev.dk/.well-known/openid-configuration
$config['openid_connect.client.generic']['settings']['client_id'] = 'mock-idp-admin;
$config['openid_connect.client.generic']['settings']['client_secret'] = 'mock-idp-admin-secret';
$config['openid_connect.client.generic']['settings']['authorization_endpoint'] = 'http://idp.selvbetjening.local.itkdev.dk/connect/authorize';
$config['openid_connect.client.generic']['settings']['token_endpoint'] = 'http://idp.selvbetjening.local.itkdev.dk/connect/token';
```

Apply a patch to allow http discovery document url:

```sh
patch --directory=vendor/itk-dev/openid-connect/ --strip=1 < patches/itk-dev/openid-connect/allow-http-discovery-document-url.patch
```

Go to `/user/login` and click “Medarbejderlogin”:

```sh
open "http://$(docker compose port nginx 8080)/user/login"
```

Sign in as `administrator` with password `administrator` (cf.
`docker-compose.override.yml`).

```php
# web/sites/default/settings.local.php
…
$config['os2web_nemlogin.settings']['OpenIDConnect'] = serialize([
  'plugin_id' => 'OpenIDConnect',
  'nemlogin_openid_connect_discovery_url' => 'http://idp.selvbetjening.local.itkdev.dk/.well-known/openid-configuration',
  'nemlogin_openid_connect_client_id' => 'mock-idp-citizen',
  'nemlogin_openid_connect_client_secret' => 'mock-idp-citizen-secret',
  'nemlogin_openid_connect_fetch_once' => 0,
  'nemlogin_openid_connect_post_logout_redirect_uri' => '/node/126',
  'nemlogin_openid_connect_user_claims' => 'cpr: CPR-nummer
email: E-mailadresse',
]);
$config['os2web_nemlogin.settings']['active_plugin_id'] = 'OpenIDConnect';
```

```sh
open "https://idp.selvbetjening.local.itkdev.dk/api/v1/user/administrator"
open "https://idp.selvbetjening.local.itkdev.dk/api/v1/user/1705880000"
```

## Reloading the OIDC configuration

Run

```sh
docker compose restart oidc-server-mock
```

to reload the OIDC configuration.
