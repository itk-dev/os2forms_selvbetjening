# Module development
The booking app is a react app (CRA). A docker compose setup has been supplied to
ease development of the app.

## Setup
```shell
itkdev-docker-compose run node npm install
itkdev-docker-compose up -d
itkdev-docker-compose exec node npm run build
itkdev-docker-compose open
```
This provides a react app ready for development.
To get proper output with working endpoints the app requires this drupal module enabled on a drupal site.

## Build
To build the code for use in the drupal module.
This will copy the compiled css and js to the library that drupal expects.
```shell
./create-build.sh
```

## Configuration within a drupal environment.
When the app is served through Drupal it will look for `window.drupalSettings` and load those.
The following drupal settings are required for a drupal connection to work properly:
```php
// Required

// Booking api endpoint, see booking api project to obtain this config
$settings['itkdev_booking_api_endpoint'] = '';
$settings['itkdev_booking_api_key'] = '';

// Endpoint provided by this drupal module (Domain = drupal website with webform)
$settings['itkdev_booking_api_endpoint_frontend'] = 'http://selvbetjening.local.itkdev.dk/';

// See 1Password for license key.
// Datafordeler Book Aarhus
$settings['itkdev_booking_df_map_username'] = '';
$settings['itkdev_booking_df_map_password'] = '';

// Fullcalendar
$settings['itkdev_booking_fullcalendar_license'] = '';
```
To obtain the proper settings see [documentation/os2forms-module-setup-example.md](os2forms-module-setup-example.md)


You can use sample data by setting the following settings:
```php
// Use sample data.
$settings['itkdev_booking_api_sample_data'] = true;
// Use sample user.
$settings['itkdev_booking_api_sample_user'] = true;
```

If served outside of Drupal, e.g. during development, a `assets/public/config.json` can be set with the config values.
See `assets/public/example_config.json` for structure of `config.json` file.

## Coding standards
Apply (automatic fixes) js coding standards
```
itkdev-docker-compose run --rm node npm run apply-coding-standards
```

Check js coding standards
```
itkdev-docker-compose run --rm node npm run check-coding-standards
```
