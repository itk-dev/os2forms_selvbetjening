# ITK Dev OpenID Connect

* Disables honeypot on the OpenID Connect login form (cf.
  [itkdev_openid_connect_honeypot_form_protections_alter](itkdev_openid_connect.module)).
* Disarms “User default page” rules during OpenID Connect login flow if it has a
  stored redirect destination (cf.
  [itkdev_openid_connect_user_default_page_login_ignore_whitelist_alter](itkdev_openid_connect.module)).


## Configuration

Edit `settings.local.php` to define your OpenID Connect secret and endpoints:

```php
$config['openid_connect.client.generic]['settings']['authorization_endpoint'] = '…';
$config['openid_connect.client.generic]['settings']['token_endpoint'] = '…';
$config['openid_connect.client.generic]['settings']['client_id'] = '…';
$config['openid_connect.client.generic]['settings']['client_secret'] = '…';
```

```php
// Map IdP groups to Drupal roles.
$config['openid_connect.settings']['role_mappings']['flow_designer'][] = 'flowdesigner';
$config['openid_connect.settings']['role_mappings']['forloeb_designer'][] = 'forloebsdesigner';
// flow- and forløbsdesigner should also be medarbejder
$config['openid_connect.settings']['role_mappings']['medarbejder'][] = 'flowdesigner';
$config['openid_connect.settings']['role_mappings']['medarbejder'][] = 'forloebsdesigner';
```

## Patches applied

Excerpt from `composer.json`:

```json
{
    …
    "extra": {
        "patches": {
            …,
            "drupal/openid_connect": {
                "Revoking group access does not reflect on applied roles (https://www.drupal.org/project/openid_connect/issues/3224128)": "https://git.drupalcode.org/project/openid_connect/-/merge_requests/31.diff"
            }
        }
    }
}
```
