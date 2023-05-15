# Maestro

Custom module: [os2forms_maestro_webform](web/modules/custom/os2forms_maestro_webform).

Maestro source code: <https://git.drupalcode.org/project/maestro/-/tree/maestro_token>

## Installation

```shell
docker compose exec phpfpm composer install
# Force updating the Maestro module (cf. https://getcomposer.org/doc/05-repositories.md#package-2)
docker compose exec phpfpm composer update drupal/maestro --no-cache
```
