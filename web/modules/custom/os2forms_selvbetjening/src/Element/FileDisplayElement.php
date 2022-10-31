<?php

namespace Drupal\os2forms_selvbetjening\Element;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform\Element\WebformComputedBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * File display element.
 *
 * @FormElement("os2forms_selvbetjening_file_display")
 */
class FileDisplayElement extends WebformComputedBase {

  /**
   * {@inheritdoc}
   */
  public static function computeValue(array $element, WebformSubmissionInterface $webformSubmission) {
    $key = $element['#webform_key'];
    $data = $webformSubmission->getData();
    if (isset($data[$key])) {
      /** @var \Drupal\file\FileStorageInterface $fileStorage */
      $fileStorage = \Drupal::entityTypeManager()->getStorage('file');
      /** @var \Drupal\file\Entity\File $file */
      $file = $fileStorage->load($data[$key]);
      if (NULL !== $file) {
        $element = [
          '#theme' => 'file_link',
          '#file' => $file,
        ];
        /** @var \Drupal\Core\Render\RendererInterface $renderer */
        $renderer = \Drupal::service('renderer');
        return (string) $renderer->render($element);
      }
    }

    return new TranslatableMarkup('No file found');
  }

}
