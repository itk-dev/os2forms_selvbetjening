<?php

namespace Drupal\os2forms_permissions_by_term\Helper;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\permissions_by_term\Service\AccessStorage;

/**
 * Helper class for maestro templates permissions by term.
 */
class MaestroTemplateHelper {
  use StringTranslationTrait;

  /**
   * Permissions by term access storage.
   *
   * @var \Drupal\permissions_by_term\Service\AccessStorage
   */
  private AccessStorage $accessStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Account proxy interface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $account;

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected ConfigFactory $configFactory;

  /**
   * The helper.
   *
   * @var \Drupal\os2forms_permissions_by_term\Helper\Helper
   */
  protected Helper $helper;

  /**
   * Maestro template helper constructor.
   *
   * @param \Drupal\permissions_by_term\Service\AccessStorage $accessStorage
   *   The permissions by term access storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The Account proxy interface.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory.
   * @param \Drupal\os2forms_permissions_by_term\Helper\Helper $helper
   *   The config factory.
   */
  public function __construct(AccessStorage $accessStorage, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $account, ConfigFactory $configFactory, Helper $helper) {
    $this->accessStorage = $accessStorage;
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account;
    $this->configFactory = $configFactory;
    $this->helper = $helper;
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * Add permission by term selection to webform "add" and "settings".
   *
   * @param array $form
   *   The form being altered.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   * @param string $hook
   *   The type of webform hook calling this method.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function maestroTemplateFormAlter(array &$form, FormStateInterface $form_state, $hook) {
    $term_data = [];
    $user = $this->entityTypeManager->getStorage('user')->load($this->account->id());
    if (1 === (int) $this->account->id()) {
      $userTerms = [];
      $permissionsByTermBundles = $this->configFactory->get('permissions_by_term.settings')->get('target_bundles');
      foreach ($permissionsByTermBundles as $bundle) {
        $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($bundle);
        foreach ($terms as $term) {
          $userTerms[] = $term->tid;
        }
      }
    }
    else {
      $userTerms = $this->accessStorage->getPermittedTids($user->id(), $user->getRoles());
    }
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($userTerms);
    foreach ($terms as $term) {
      $term_data[$term->id()] = $term->label();
    }

    // Remove any options that allow anonymous access to the maestro template.
    $anonymousTerms = $this->accessStorage->getPermittedTids(0, ['anonymous']);
    foreach ($anonymousTerms as $termId) {
      unset($term_data[$termId]);
    }

    if ('settings' === $hook) {
      /** @var \Drupal\Core\Entity\EntityForm $meastroSettingsForm */
      $meastroSettingsForm = $form_state->getFormObject();
      /** @var \Drupal\Core\Config\Entity\ThirdPartySettingsInterface $mastroTemplate */
      $mastroTemplate = $meastroSettingsForm->getEntity();
      $defaultSettings = $mastroTemplate->getThirdPartySetting('os2forms_permissions_by_term', 'maestro_template_permissions_by_term_settings');
    }

    $form['maestro_template_permissions_by_term'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => 'Meastro template access',
      '#tree' => TRUE,
      '#weight' => -99,
    ];

    $form['maestro_template_permissions_by_term']['os2forms_access'] = [
      '#type' => 'checkboxes',
      '#required' => TRUE,
      '#title' => $this->t('Access'),
      '#default_value' => $defaultSettings ?? [],
      '#options' => $term_data,
      '#description' => $this->t('Limit access to this template.'),
    ];

    // Set access value automatically if user only has one term option.
    if ('add' === $hook && 1 === count($term_data)) {
      $form['maestro_template_permissions_by_term']['os2forms_access']['#disabled'] = TRUE;
      $form['maestro_template_permissions_by_term']['os2forms_access']['#value'] = [array_key_first($term_data) => array_key_first($term_data)];
    }

    $form['actions']['submit']['#submit'][] = [$this, 'maestroTemplateSubmit'];
  }

  /**
   * Implementation of hook_ENTITY_TYPE_access().
   *
   * Change access on maestro templates related operations.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $maestroTemplate
   *   The entity to set access for.
   * @param string $operation
   *   The operation being performed on the webform.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The resulting access permission.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function maestroTemplateAccess(ConfigEntityInterface $maestroTemplate, $operation, AccountInterface $account) {
    if (1 === (int) $account->id()) {
      return AccessResult::neutral();
    }
    $user = $this->entityTypeManager->getStorage('user')->load($account->id());
    $userTerms = $this->accessStorage->getPermittedTids($user->id(), $user->getRoles());
    $maestroTemplatePermissionsByTerm = $maestroTemplate->getThirdPartySetting('os2forms_permissions_by_term', 'maestro_template_permissions_by_term_settings');

    switch ($operation) {
      case 'view':
      case 'update':
      case 'delete':
        // Allow access if no term is set for the template or a maestro template
        // term match the users term.
        return empty($maestroTemplatePermissionsByTerm) || !empty(array_intersect($maestroTemplatePermissionsByTerm, $userTerms))
          ? AccessResult::neutral()
          : AccessResult::forbidden();
    }

    return AccessResult::neutral();
  }

  /**
   * Custom submit handler for maestro template add/edit form.
   *
   * Set permission by term as a thirdPartySetting of the maestro template.
   *
   * @param array $form
   *   The maestro template add/edit form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   */
  public function maestroTemplateSubmit(array $form, FormStateInterface $form_state) {
    // Get the settings from the maestro templates config entity.
    /** @var \Drupal\Core\Entity\EntityForm $maestroTemplateSettingsForm */
    $maestroTemplateSettingsForm = $form_state->getFormObject();
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $maestroTemplate */
    $maestroTemplate = $maestroTemplateSettingsForm->getEntity();
    $maestroTemplate->setThirdPartySetting(
      'os2forms_permissions_by_term',
      'maestro_template_permissions_by_term_settings',
      $form_state->getValue([
        'maestro_template_permissions_by_term',
        'os2forms_access',
      ])
    );
    $maestroTemplate->save();
  }

  /**
   * Implements hook_field_widget_multivalue_WIDGET_TYPE_form_alter().
   *
   * Alter the field webform_entity_reference widget.
   * Hide webform options from maestro templates if user is not allowed to
   * update the webform.
   *
   * @param array $form
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   * @param string $form_id
   *   The id of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function fieldWidgetMaestroTaskEditFormAlter(array &$form, FormStateInterface $form_state, string $form_id) {
    if ('template_edit_task' == $form_id) {
      if (array_key_exists('webform_machine_name', $form)) {
        foreach ($form['webform_machine_name']['#options'] as $key => $option) {
          if (!$option instanceof TranslatableMarkup) {
            $webform = $this->entityTypeManager->getStorage('webform')->load($key);
            /** @var \Drupal\webform\WebformInterface $webform */
            $accessResult = $this->helper->webformAccess($webform, 'update', $this->account);
            if ($accessResult instanceof AccessResultForbidden) {
              unset($form['webform_machine_name']['#options'][$key]);
            }
          }
        }
      }
    }
  }

}
