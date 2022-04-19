<?php

namespace Drupal\os2forms_webform_list;

use Drupal\webform\WebformEntityListBuilder;

/**
 * Defines a class to build a listing of webform entities.
 *
 * @see \Drupal\webform\Entity\Webform
 */
class CustomWebformEntityListBuilder extends WebformEntityListBuilder {

  /**
   * Alter the webform entity list builder query method.
   *
   * Force the list builder query to respect webform access and properly hide
   * inaccessible webforms from the list.
   *
   * @param string $keys
   *   (optional) Search key.
   * @param string $category
   *   (optional) Category.
   * @param string $state
   *   (optional) Webform state. Can be 'open' or 'closed'.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query.
   */
  protected function getQuery($keys = '', $category = '', $state = '') {
    $query = parent::getQuery($keys, $category, $state);

    // Setup a required condition for the list builder to respect webform update
    // access.
    $webform_ids_permissions_by_term = [];
    /** @var \Drupal\webform\WebformInterface[] $webforms */
    $webforms = $this->getStorage()->loadMultiple();
    foreach ($webforms as $webform) {
      $access = $webform->access('update');
      if ($access) {
        $webform_ids_permissions_by_term[] = $webform->id();
      }
    }

    $query->condition('id', $webform_ids_permissions_by_term, 'IN');

    return $query;
  }

}
