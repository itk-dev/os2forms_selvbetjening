<?php

namespace Drupal\os2forms_webform_list;

use Drupal\webform\WebformEntityListBuilder;
use Drupal\webform\WebformInterface;

/**
 * Defines a class to build a listing of webform entities.
 *
 * @see \Drupal\webform\Entity\Webform
 */
class customWebformEntityListBuilder extends WebformEntityListBuilder {

  /**
   * Alter the webform entity list builder query method.
   *
   * Force the list builder query to respect webform access and properly hide inaccessible webforms from the list.
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
    $query = $this->getStorage()->getQuery();

    // Filter by key(word).
    if ($keys) {
      $or = $query->orConditionGroup()
        ->condition('id', $keys, 'CONTAINS')
        ->condition('title', $keys, 'CONTAINS')
        ->condition('description', $keys, 'CONTAINS')
        ->condition('category', $keys, 'CONTAINS')
        ->condition('elements', $keys, 'CONTAINS');

      // Users and roles we need to scan all webforms.
      $access_value = NULL;
      if ($accounts = $this->userStorage->loadByProperties(['name' => $keys])) {
        $account = reset($accounts);
        $access_type = 'users';
        $access_value = $account->id();
      }
      elseif ($role = $this->roleStorage->load($keys)) {
        $access_type = 'roles';
        $access_value = $role->id();
      }
      if ($access_value) {
        // Collect the webform ids that the user or role has access to.
        $webform_ids = [];
        /** @var \Drupal\webform\WebformInterface $webforms */
        $webforms = $this->getStorage()->loadMultiple();
        foreach ($webforms as $webform) {
          $access_rules = $webform->getAccessRules();
          foreach ($access_rules as $access_rule) {
            if (!empty($access_rule[$access_type]) && in_array($access_value, $access_rule[$access_type])) {
              $webform_ids[] = $webform->id();
              break;
            }
          }
        }
        if ($webform_ids) {
          $or->condition('id', $webform_ids, 'IN');
        }
        // Also check the webform's owner.
        if ($access_type === 'users') {
          $or->condition('uid', $access_value);
        }
      }
      $query->condition($or);
    }

    // Filter by category.
    if ($category) {
      $query->condition('category', $category);
    }

    // Setup a required condition for the list builder to respect webform update access.
    $webform_ids_permissions_by_term = [];
    /** @var \Drupal\webform\WebformInterface $webforms */
    $webforms = $this->getStorage()->loadMultiple();
    foreach ($webforms as $webform) {
      $access = $webform->access('update');
      if($access) {
        $webform_ids_permissions_by_term[] = $webform->id();
      }
    }

    $query->condition('id', $webform_ids_permissions_by_term, 'IN');


    // Filter by (form) state.
    switch ($state) {
      case WebformInterface::STATUS_OPEN;
      case WebformInterface::STATUS_CLOSED;
      case WebformInterface::STATUS_SCHEDULED;
        $query->condition('status', $state);
        break;
    }

    // Always filter by archived state.
    $query->condition('archive', $state === WebformInterface::STATUS_ARCHIVED ? 1 : 0);

    // Filter out templates if the webform_template.module is enabled.
    if ($this->moduleHandler()->moduleExists('webform_templates') && $state !== WebformInterface::STATUS_ARCHIVED) {
      $query->condition('template', FALSE);
    }
    return $query;
  }
}
