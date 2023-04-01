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

```sh
# web/sites/default/settings.local.php
…

// http://idp.selvbetjening.local.itkdev.dk/.well-known/openid-configuration
$config['openid_connect.client.generic']['settings']['client_id'] = 'client-credentials-mock-client';
$config['openid_connect.client.generic']['settings']['client_secret'] = 'client-credentials-mock-client-secret';
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

Sign in as `User1` with password `pwd` (cf. `docker-compose.override.yml`).

## Reloading the OIDC configuration

Run

```sh
docker compose restart oidc-server-mock
```

to reload the OIDC configuration.
