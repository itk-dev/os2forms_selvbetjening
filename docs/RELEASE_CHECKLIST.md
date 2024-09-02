# Release checklist

## Webform upgrade

If `drupal/webform` is upgraded it may require repair of existing,
webform configuration on [Webform Advanced Configuration](https://selvbetjening.aarhuskommune.dk/en/admin/structure/webform/config/advanced).

## Libraries download

Remember to run

```sh
docker compose exec phpfpm vendor/bin/drush webform:libraries:download
```
