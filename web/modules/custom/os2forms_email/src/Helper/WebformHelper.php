<?php

namespace Drupal\os2forms_email\Helper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The webform helper.
 */
class WebformHelper {
  use StringTranslationTrait;

  /**
   * Implements hook_webform_third_party_settings_form_alter().
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function webformThirdPartySettingsFormAlter(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\Core\Entity\EntityForm $formObject */
    $formObject = $form_state->getFormObject();
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $formObject->getEntity();

    $defaultValues = $webform->getThirdPartySetting('os2forms', 'os2forms_email');
    $form['third_party_settings']['os2forms']['os2forms_email'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('OS2Forms email'),
      '#tree' => TRUE,
    ];



    $form['third_party_settings']['os2forms']['os2forms_email']['enabled'] = [
      '#title' => $this->t('Enable'),
      '#type' => 'checkbox',
      '#default_value' => $defaultValues['enabled'] ?? FALSE,
      '#description' => $this->t('Enable notification upon sending emails with large attachments'),
    ];

    $form['third_party_settings']['os2forms']['os2forms_email']['emails'] = [
      '#title' => $this->t('Emails'),
      '#type' => 'textarea',
      '#default_value' => $defaultValues['emails'] ?? NULL,
      '#description' => $this->t('Send a notification to these email adresses (one per line)'),
      '#states' => [
        // Show this textfield only if above is enabled.
        'visible' => [
          ':input[name="third_party_settings[os2forms][os2forms_email][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }
}
