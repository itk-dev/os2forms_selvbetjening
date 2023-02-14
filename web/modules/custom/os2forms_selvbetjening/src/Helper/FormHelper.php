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
    // Add description to the message body section of the email handler.
    if ('webform_handler_form' === $form_id && 'email' === ($form['#webform_handler_plugin_id'] ?? NULL)) {

      $config = $this->config->get('os2forms_selvbetjening');

      if ($email_body_description = $config->get('email_body_description')) {
        $form['settings']['message']['body']['#description'] = $email_body_description;
      }
    }
  }

}
