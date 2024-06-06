<?php

namespace Drupal\os2forms_email_handler\Helper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The webform helper.
 */
class WebformHelper {
  use StringTranslationTrait;

  public const MODULE_NAME = 'os2forms_email_handler';

  /**
   * Implements hook_webform_third_party_settings_form_alter().
   */
  public function webformThirdPartySettingsFormAlter(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\Core\Entity\EntityForm $formObject */
    $formObject = $form_state->getFormObject();
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $formObject->getEntity();

    $defaultValues = $webform->getThirdPartySetting('os2forms', self::MODULE_NAME);
    $form['third_party_settings']['os2forms'][self::MODULE_NAME] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('OS2Forms email handler'),
      '#tree' => TRUE,
    ];

    $form['third_party_settings']['os2forms'][self::MODULE_NAME]['enabled'] = [
      '#title' => $this->t('Enable'),
      '#type' => 'checkbox',
      '#default_value' => $defaultValues['enabled'] ?? FALSE,
      '#description' => $this->t('Enable notification upon sending emails with large attachments'),
    ];

    $form['third_party_settings']['os2forms'][self::MODULE_NAME]['email_recipients'] = [
      '#title' => $this->t('Email recipients'),
      '#type' => 'textarea',
      '#default_value' => $defaultValues['email_recipients'] ?? NULL,
      '#description' => $this->t('Send a notification to these email addresses (one per line)'),
      '#states' => [
        // Show this textfield only if above is enabled.
        'visible' => [
          ':input[name="third_party_settings[os2forms][' . self::MODULE_NAME . '][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

}
