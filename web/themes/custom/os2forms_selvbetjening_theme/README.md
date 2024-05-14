# selvbetjening.aarhuskommune.dk

## PDF templates

Two templates, `os2forms-attachment--webform-submission.html.twig` and
`os2forms-selvbetjening-maestro-notification-pdf-html.html.twig`, are used to render os2forms attachment and maestro notification (pdf) html,
respectively (the templates are used to render PDF files sent as Digital post).

The maestro notification pdf template should be configured on
`admin/config/system/os2forms_forloeb` as

```sh
themes/custom/os2forms_selvbetjening_theme/templates/pdf/os2forms-selvbetjening-maestro-notification-pdf-html.html.twig
```

whereas the os2forms attachment template automatically should be used.

To allow usage of a common stylesheet in the two templates you
must configure `base_url` in `settings.local.php`

```php
$settings['base_url'] = 'http://nginx:8080';
```

and disable default css in the entity print module. This is done by
unchecking `Enable Default CSS` on `admin/config/content/entityprint`.

## Theme usage

```sh
docker-compose run --rm node yarn --cwd /app/web/themes/custom/os2forms_selvbetjening_theme install
docker-compose run --rm node yarn --cwd /app/web/themes/custom/os2forms_selvbetjening_theme build
```

## Coding standards

```sh
docker-compose run --rm node yarn --cwd /app/web/themes/custom/os2forms_selvbetjening_theme check-coding-standards
```

```sh
docker-compose run --rm node yarn --cwd /app/web/themes/custom/os2forms_selvbetjening_theme apply-coding-standards
```
