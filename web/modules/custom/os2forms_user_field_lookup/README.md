# OS2Forms User Field Lookup

Use user data in forms.

## Installation

Require it with composer:

```sh
composer require "itk-dev/os2forms_user_field_lookup"
```

Enable the module:

```sh
drush pm:enable os2forms_user_field_lookup
```

## Usage

Add a User Field Element to a form and select a user field name on the element.
When the form is displayed by an authenticated user, the field will be populated
with the value of the selected field.

### Elements

* User Field Element
* User Field Element (checkbox)

## Coding standards

Run phpcs with the provided configuration:

```sh
composer coding-standards-check

// Apply coding standards
composer coding-standards-apply
```
