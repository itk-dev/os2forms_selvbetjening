# OS2Forms Permission Alterations

Module for altering permissions on entities and routes.

## Alterations

Module contains the following alterations

### Leaflet layers

* Added `administer leaflet layers` permission
* Changed `admin_permission` from `administer site configuration` to
`administer leaflet layers` on the `Drupal\leaflet_layers\Entity\MapLayer`
and `Drupal\leaflet_layers\Entity\MapBundle` entities.
