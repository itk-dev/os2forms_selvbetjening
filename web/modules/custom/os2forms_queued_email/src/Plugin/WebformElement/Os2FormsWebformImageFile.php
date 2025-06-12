<?php

namespace Drupal\os2forms_queued_email\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformImageFile;

/**
 * OS2Forms webform image file.
 *
 * @see Os2FormsQueuedEmailFileTrait
 */
class Os2FormsWebformImageFile extends WebformImageFile {

  use Os2FormsQueuedEmailFileTrait;

}
