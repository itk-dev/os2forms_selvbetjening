<?php

namespace Drupal\os2forms_maestro_webform\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings for os2forms_maestro_webform.
 */
class SettingsForm extends ConfigFormBase {
  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'os2forms_maestro_webform.settings';

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    readonly private RoleStorageInterface $roleStorage
  ) {
    parent::__construct($configFactory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getStorage('user_role'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_maestro_webform_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $roles = $this->roleStorage->loadMultiple();
    $form['known_anonymous_roles'] = [
      '#title' => $this->t('Known anonymous roles'),
      '#type' => 'checkboxes',
      '#options' => array_map(static fn (RoleInterface $role) => $role->label(), $roles),
      '#default_value' => $config->get('known_anonymous_roles') ?: [],
      '#description' => $this->t('Roles that can act as “known anonymous”'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    $this->config(static::SETTINGS)
      ->set('known_anonymous_roles', $formState->getValue('known_anonymous_roles'))
      ->save();

    parent::submitForm($form, $formState);
  }

}
