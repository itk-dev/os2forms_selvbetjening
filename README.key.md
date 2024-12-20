# Key notes

``` json
// composer.json
    "require": {
        "itk-dev/os2forms_nemlogin_openid_connect": "dev-feature/os2web_key",
        …
        "os2forms/os2forms": "dev-feature/os2web_key as 3.21.0",
        "os2forms/os2forms_get_organized": "dev-feature/os2web_key",
        "os2web/os2web_datalookup": "dev-feature/os2web_key as 2.0.2",
        "os2forms/os2forms_fasit": "dev-feature/os2web_key",
    },
    "repositories": [
        { "type": "vcs", "url": "https://github.com/itk-dev/os2forms" },
        { "type": "vcs", "url": "https://github.com/itk-dev/os2forms_fasit" },
        { "type": "vcs", "url": "https://github.com/itk-dev/os2forms_get_organized" },
        { "type": "vcs", "url": "https://github.com/itk-dev/os2forms_nemlogin_openid_connect" },
        { "type": "vcs", "url": "https://github.com/itk-dev/os2web_datalookup" },
        …
    ],
```

Is `os2forms/os2forms_fasit` actually used (yet)?
