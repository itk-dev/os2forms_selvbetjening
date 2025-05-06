<?php

namespace Drupal\os2forms_queued_email\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformVideoFile;

/**
 * OS2Forms webform video file.
 *
 * @see Os2FormsQueuedEmailFileTrait
 */
class Os2FormsWebformVideoFile extends WebformVideoFile {

  use Os2FormsQueuedEmailFileTrait;

}
