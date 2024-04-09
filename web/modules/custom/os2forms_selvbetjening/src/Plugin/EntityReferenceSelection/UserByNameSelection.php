<?php

namespace Drupal\os2forms_selvbetjening\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for the node entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:user_by_name",
 *   label = @Translation("User by name field selection"),
 *   entity_types = {"user"},
 *   group = "default",
 *   weight = 1
 * )
 */
class UserByNameSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   *
   * Heavily inspired by
   * Drupal\user\Plugin\EntityReferenceSelection\UserSelection,
   * with a slight modification to the field condition.
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match_operator, $match_operator);

    $configuration = $this->getConfiguration();

    // Filter out the Anonymous user if the selection handler is configured to
    // exclude it.
    if (!$configuration['include_anonymous']) {
      $query->condition('uid', 0, '<>');
    }

    if (isset($match)) {
      $query->condition('field_name', $match, $match_operator);
    }

    // Filter by role.
    if (!empty($configuration['filter']['role'])) {
      $query->condition('roles', $configuration['filter']['role'], 'IN');
    }

    // Adding the permission check is sadly insufficient for users: core
    // requires us to also know about the concept of 'blocked' and 'active'.
    if (!$this->currentUser->hasPermission('administer users')) {
      $query->condition('status', 1);
    }

    return $query;
  }

}
