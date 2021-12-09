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
 * Helper class for maestro templates permissions by term.
 */
class MaestroTemplateHelper {

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
  public function maestroTemplateformAlter(array &$form, FormStateInterface $form_state, $hook) {
    $term_data = [];
    $user = $this->entityTypeManager->getStorage('user')->load($this->account->id());
    $userTerms = $this->accessStorage->getPermittedTids($user->id(), $user->getRoles());
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($userTerms);
    foreach ($terms as $term) {
      $term_data[$term->id()] = $term->label();
    }

    /*
    // Make sure title is first when creating a new webform.
    if ('add' === $hook) {
      $form['title']['#weight'] = -100;
    }
*/
    // Get default settings for webform.
    /*
    if ('settings' === $hook) {
      $webform_settings_form = $form_state->getFormObject();
      $webform = $webform_settings_form->getEntity();
      $defaultSettings = $webform->getThirdPartySetting('os2forms_permissions_by_term', 'settings');
    }

    */
    $form['os2forms_permissions_by_term'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => 'Meastro template access',
      '#tree' => TRUE,
      '#weight' => -99,
    ];

    $form['os2forms_permissions_by_term']['os2forms_access'] = [
      '#type' => 'checkboxes',
      '#required' => TRUE,
      '#title' => t('Access'),
      '#default_value' => $defaultSettings ?? [],
      '#options' => $term_data,
      '#description' => t('Limit access to this template.'),
    ];

    // Set access value automatically if user only has one term option.
    if ('add' === $hook && 1 === count($term_data)) {
      $form['os2forms_permissions_by_term']['os2forms_access']['#disabled'] = TRUE;
      $form['os2forms_permissions_by_term']['os2forms_access']['#value'] = [array_key_first($term_data) => array_key_first($term_data)];
    }

    $form['actions']['submit']['#submit'][] = [$this, 'webformSubmit'];
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
    /*
    $webform_settings_form = $form_state->getFormObject();
    $webform = $webform_settings_form->getEntity();
    $webform->setThirdPartySetting('os2forms_permissions_by_term', 'settings', $form_state->getValue(['os2forms_permissions_by_term', 'os2forms_access']));
    $webform->save();
    */
  }
}
