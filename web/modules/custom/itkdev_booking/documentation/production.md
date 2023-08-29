# Production
For this module to work in production it requires a drupal 8.8 or greater with the webform module enabled.

## Setup
Follow [documentation/os2forms-module-setup-example.md](os2forms-module-setup-example.md) steps 6-9 for information about setting up the project for production.
Obtain booking api information from the booking api production environment.

Modify settings.local.php
```php
$settings['itkdev_booking_api_endpoint'] = '*** Get from book aarhus project ***';
$settings['itkdev_booking_api_key'] = '*** Get from book aarhus project ***';
$settings['itkdev_booking_api_endpoint_frontend'] = '*** Get from os2forms project ***';
$settings['itkdev_booking_df_map_username'] = '*** Get from 1password ***';
$settings['itkdev_booking_df_map_password'] = '*** Get from 1password ***';
$settings['itkdev_booking_fullcalendar_license'] = '*** Get from 1password ***';
```

## Release
New releases of this project follows semantic versioning principles and should be tagged as such.