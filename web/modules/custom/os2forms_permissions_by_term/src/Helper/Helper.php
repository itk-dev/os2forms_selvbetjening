<?php

namespace Drupal\os2forms_permissions_by_term\Helper;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\permissions_by_term\Service\AccessStorage;
use Drupal\webform\WebformInterface;

/**
 * Helper class for os2forms permissions by term.
 */
class Helper {

  /**
   * Permissions by term access storage
   *
   * @var \Drupal\permissions_by_term\Service\AccessStorage
   */
  private $accessStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Account proxy interface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Helper constructor.
   *
   * @param \Drupal\permissions_by_term\Service\AccessStorage $accessStorage
   *   The permissions by term access storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The Account proxy interface.
   */
  public function __construct(AccessStorage $accessStorage, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $account) {
    $this->accessStorage = $accessStorage;
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account;
  }

  /**
   * Implementation of hook_form_FORM_ID_alter().
   *
   * Add permission by term selection to webform "add" and "settings".
   *
   * @param array $form
   *   The form being altered.
   * @param FormStateInterface $form_state
   *   The state of the form.
   * @param $hook
   *   The type of webform hook calling this method.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   */
  public function webformAlter(array &$form, FormStateInterface $form_state, $hook) {
    $term_data = [];
    $user = $this->entityTypeManager->getStorage('user')->load($this->account->id());
    $userTerms = $this->accessStorage->getPermittedTids($user->id(), $user->getRoles());
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($userTerms);
    foreach ($terms as $term) {
      $term_data[$term->id()] = $term->label();
    }

    // Make sure title is first when creating a new webform.
    if ('add' === $hook) {
      $form['title']['#weight'] = -100;
    }

    // Get default settings for webform.
    if ('settings' === $hook) {
      $webform_settings_form = $form_state->getFormObject();
      $webform = $webform_settings_form->getEntity();
      $defaultSettings = $webform->getThirdPartySetting('os2forms_permissions_by_term', 'settings');
    }

    $form['os2forms_permissions_by_term'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => 'Webform access',
      '#tree' => TRUE,
      '#weight' => -99,
    ];

    $form['os2forms_permissions_by_term']['os2forms_access'] = [
      '#type' => 'checkboxes',
      '#required' => TRUE,
      '#title' => t('Access'),
      '#default_value' => $defaultSettings ?? [],
      '#options' => $term_data,
      '#description' => t('Limit access to this webform.'),
    ];

    // Set access value automatically if user only has one term option.
    if ('add' === $hook && 1 === count($term_data)) {
      $form['os2forms_permissions_by_term']['os2forms_access']['#disabled'] = TRUE;
      $form['os2forms_permissions_by_term']['os2forms_access']['#value'] = [array_key_first($term_data) => array_key_first($term_data)];
    }

    $form['actions']['submit']['#submit'][] = [$this, 'webformSubmit'];
  }

  /**
   * Implementation of hook_ENTITY_TYPE_access().
   *
   * Check access on webform related operations.
   *
   * @param WebformInterface $webform
   *   The webform we check access for.
   * @param $operation
   *   The operation being performed on the webform.
   * @param AccountInterface $account
   *   The current user.
   * @return \Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultNeutral
   *   The resulting access permission.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function webformAccess(WebformInterface $webform, $operation, AccountInterface $account) {
    $user = $this->entityTypeManager->getStorage('user')->load($account->id());
    $userTerms = $this->accessStorage->getPermittedTids($user->id(), $user->getRoles());
    $webformPermissionsByTerm = $webform->getThirdPartySetting('os2forms_permissions_by_term', 'settings');
    switch ($operation) {
      case 'view':
        // We don't use permission by term to determine access to the actual webform.
        // This could probably be removed, but is left in to show we are aware of this operation.
        return AccessResult::neutral();

      case 'update':
      case 'delete':
      case 'duplicate':
      case 'test':
      case 'submission_page':
      case 'submission_view_any':
      case 'submission_view_own':
      case 'submission_purge_any':
        // Allow access if no term is set for the form or a webform term match the users term.
      return empty($webformPermissionsByTerm) || !empty(array_intersect($webformPermissionsByTerm, $userTerms))
          ? AccessResult::neutral()
          : AccessResult::forbidden();
    }
  }

  /**
   * Custom submit handler for webform add/edit form.
   *
   * Set permission by term as a thirdPartySetting of the webform.
   *
   * @param array $form
   *   The webform add/edit form.
   * @param FormStateInterface $form_state
   *   The state of the form.
   */
  public function webformSubmit(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Get the settings from the webform config entity.
    $webform_settings_form = $form_state->getFormObject();
    $webform = $webform_settings_form->getEntity();
    $webform->setThirdPartySetting('os2forms_permissions_by_term', 'settings', $form_state->getValue(['os2forms_permissions_by_term', 'os2forms_access']));
    $webform->save();
  }
}
