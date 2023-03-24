<?php

namespace Drupal\os2forms_selvbetjening\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form Helper class, for altering forms.
 */
class FormHelper {

  /**
   * The ConfigFactoryInterface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $config;

  /**
   * Constructs a FormHelper.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config;
  }

  /**
   * Allows altering of forms.
   */
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id) {
    $config = $this->config->get('os2forms_selvbetjening');

    // Add description to the message body section of the email handler.
    if ('webform_handler_form' === $form_id && 'email' === ($form['#webform_handler_plugin_id'] ?? NULL)) {
      if ($email_body_description = $config->get('email_body_description')) {
        $form['settings']['message']['body']['#description'] = $email_body_description;
      }
    }

    // Add description to category choice in Webform Settings.
    if ('webform_settings_form' === $form_id) {
      if ($webform_category_description = $config->get('webform_category_description')) {
        $form['general_settings']['category']['#description'] = $webform_category_description;
      }
    }

    // Add description to category choice when adding new Webform.
    if ('webform_add_form' == $form_id) {
      if ($webform_category_description = $config->get('webform_category_description')) {
        $form['category']['#description'] = $webform_category_description;
      }
    }
  }

}
