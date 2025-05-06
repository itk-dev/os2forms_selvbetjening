<?php

namespace Drupal\os2forms_queued_email\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\ManagedFile;

/**
 * OS2Forms managed file.
 *
 * @see Os2FormsQueuedEmailFileTrait
 */
class Os2FormsManagedFile extends ManagedFile {

  use Os2FormsQueuedEmailFileTrait;

}
