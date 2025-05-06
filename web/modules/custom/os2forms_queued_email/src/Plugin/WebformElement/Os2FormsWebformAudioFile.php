<?php

namespace Drupal\os2forms_queued_email\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformAudioFile;

/**
 * OS2Forms webform audio file.
 *
 * @see Os2FormsQueuedEmailFileTrait
 */
class Os2FormsWebformAudioFile extends WebformAudioFile {

  use Os2FormsQueuedEmailFileTrait;

}
