# selvbetjening.aarhuskommune.dk examples

```sh
docker-compose exec phpfpm vendor/bin/drush pm:enable os2forms_selvbetjening_examples
```

```sh
docker-compose exec phpfpm vendor/bin/drush pm:uninstall os2forms_selvbetjening_examples
docker-compose exec phpfpm vendor/bin/drush pm:enable os2forms_selvbetjening_examples
```

## Forms

```sh
open "http://$(docker-compose port nginx 80)/admin/structure/webform?search=&category=Example"
```

/form/example-flow-step-1

### Clean up

Remove webform submissions:

```sh
docker-compose exec phpfpm vendor/bin/drush webform:purge
```

Maestro tasks:

```sh
docker-compose exec phpfpm vendor/bin/drush os2forms-selvbetjening-examples:maestro:task
```

## Flows

```sh
open "http://$(docker-compose port nginx 80)/maestro/templates/list"
```
