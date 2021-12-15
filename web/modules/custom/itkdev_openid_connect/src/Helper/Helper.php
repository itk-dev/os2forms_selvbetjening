<?php

namespace Drupal\itkdev_openid_connect\Helper;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\permissions_by_term\Service\AccessStorage;
use Drupal\user\UserInterface;

/**
 * Helper class for itkdev openid connect.
 */
class Helper {
  /**
   * Permissions by term access storage
   *
   * @var \Drupal\permissions_by_term\Service\AccessStorage
   */
  protected AccessStorage $accessStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Helper constructor.
   *
   * @param \Drupal\permissions_by_term\Service\AccessStorage $accessStorage
   *   The permissions by term access storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(AccessStorage $accessStorage, EntityTypeManagerInterface $entity_type_manager) {
    $this->accessStorage = $accessStorage;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Add permission term when saving user info.
   *
   * @param UserInterface $account
   *   User account.
   * @param array $context
   *   Context information from open id connect module.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function userInfoSave(UserInterface $account, array $context) {
    $vid = 'user_affiliation';
    $claim = 'Office';
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vid);
    foreach ($terms as $term) {
      $termsObj = $this->entityTypeManager->getStorage('taxonomy_term')->load($term->tid);
      $field_claim = $termsObj->field_claim->value;
      if (isset($field_claim) && $field_claim === $context['user_data'][$claim]) {
        $this->accessStorage->addTermPermissionsByUserIds([$account->id()], $term->tid);
      }
    }
  }
}
