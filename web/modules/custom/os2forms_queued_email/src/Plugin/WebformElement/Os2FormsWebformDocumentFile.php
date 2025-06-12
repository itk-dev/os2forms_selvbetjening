<?php

namespace Drupal\os2forms_queued_email\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformDocumentFile;

/**
 * OS2Forms webform document file.
 *
 * @see Os2FormsQueuedEmailFileTrait
 */
class Os2FormsWebformDocumentFile extends WebformDocumentFile {

  use Os2FormsQueuedEmailFileTrait;

}
