# Key notes

"os2web_key" branches are required (via custom repositories) in `composer.json`:

``` diff
diff --git a/composer.json b/composer.json
index 752d10a..48c44e8 100644
--- a/composer.json
+++ b/composer.json
@@ -25,17 +25,18 @@
         "drupal/webform_translation_permissions": "^2.0",
         "fig/http-message-util": "^1.1",
         "itk-dev/os2forms_failed_jobs": "^1.5",
-        "itk-dev/os2forms_nemlogin_openid_connect": "^2.2",
+        "itk-dev/os2forms_nemlogin_openid_connect": "dev-feature/os2web_key as 2.3.0",
         "itk-dev/os2forms_user_field_lookup": "^1.1",
         "itk-dev/web_accessibility_statement": "^1.1",
-        "os2forms/os2forms": "^4.0",
+        "os2forms/os2forms": "dev-feature/os2web_key as 4.1.0",
         "os2forms/os2forms_forloeb_profile": "^1.15",
-        "os2forms/os2forms_get_organized": "^1.4",
+        "os2forms/os2forms_get_organized": "dev-feature/os2web_key as 1.5.0",
         "os2forms/os2forms_organisation": "^2.1",
         "os2forms/os2forms_payment": "^1.0",
         "os2forms/os2forms_rest_api": "^2.2",
         "os2forms/os2forms_sync": "^1.2",
-        "os2forms/os2forms_webform_submission_log": "^1.1"
+        "os2forms/os2forms_webform_submission_log": "^1.1",
+        "os2web/os2web_datalookup": "dev-feature/os2web_key as 2.1.0"
     },
     "require-dev": {
         "drupal/core-dev": "^10",
@@ -50,6 +51,18 @@
         "drupal/drupal": "*"
     },
     "repositories": [
+        {
+            "type": "vcs",
+            "url": "https://github.com/itk-dev/os2forms"
+        },
+        {
+            "type": "vcs",
+            "url": "https://github.com/itk-dev/os2forms_get_organized"
+        },
+        {
+            "type": "vcs",
+            "url": "https://github.com/itk-dev/os2web_datalookup"
+        },
         {
             "type": "composer",
             "url": "https://packages.drupal.org/8"
@@ -70,6 +83,7 @@
             "phpstan/extension-installer": true,
             "simplesamlphp/composer-module-installer": true,
             "simplesamlphp/composer-xmlprovider-installer": true,
+            "tbachert/spi": true,
             "zaporylie/composer-drupal-optimizations": true
         },
         "discard-changes": true,
```

## `os2forms`

* `/admin/os2forms_fasit/settings`

## `os2forms_get_organized`

* `/admin/os2forms_get_organized/settings`

## `os2forms_nemlogin_openid_connect`

* `/admin/config/system/os2web-nemlogin/openid-connect-nemlogin`

## `os2web_datalookup`

* `/admin/config/system/os2web-datalookup`
* `/admin/config/system/os2web-datalookup/serviceplatformen-cvr`
* `/admin/config/system/os2web-datalookup/datafordeler-pnumber`
* `/admin/config/system/os2web-datalookup/serviceplatformen-cpr`
* `/admin/config/system/os2web-datalookup/serviceplatformen-p-number`
* `/admin/config/system/os2web-datalookup/datafordeler-cvr`
* `/admin/config/system/os2web-datalookup/serviceplatformen-cpr-extended`

## Development

Updating key branches:

``` shell name=key-branches-update
docker compose exec phpfpm composer update itk-dev/os2forms_nemlogin_openid_connect os2forms/os2forms os2forms/os2forms_get_organized os2web/os2web_datalookup
```
