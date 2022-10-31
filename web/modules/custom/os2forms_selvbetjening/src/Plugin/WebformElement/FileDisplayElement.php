<?php

namespace Drupal\os2forms_selvbetjening\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformMarkup;

/**
 * File display element.
 *
 * @WebformElement(
 *   id = "os2forms_selvbetjening_file_display",
 *   label = @Translation("File display"),
 *   description = @Translation("File display element description"),
 *   category = @Translation("File")
 * )
 */
class FileDisplayElement extends WebformMarkup {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'readonly' => TRUE,
    ] + parent::defineDefaultProperties();
  }

}
