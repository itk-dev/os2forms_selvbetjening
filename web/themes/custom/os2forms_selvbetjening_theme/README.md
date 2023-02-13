# selvbetjening.aarhuskommune.dk

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
