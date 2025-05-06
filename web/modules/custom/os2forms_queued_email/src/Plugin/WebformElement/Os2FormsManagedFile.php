<?php

namespace Drupal\os2forms_queued_email\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\ManagedFile;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Extends 'managed_file' element.
 *
 * @WebformElement(
 *   id = "os2forms_managed_file",
 *   api = "https://api.drupal.org/api/drupal/core!modules!file!src!Element!ManagedFile.php/class/ManagedFile",
 *   label = @Translation("File"),
 *   description = @Translation("Provides a form element for uploading and saving a file."),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 * )
 */
class Os2FormsManagedFile extends ManagedFile {

  /**
   * {@inheritdoc}
   *
   * OS2Forms managed file element.
   *
   * Copied from WebformManagedFileBase::getEmailAttachments.
   * Only change is that filecontent is not passed on.
   *
   * @see parent::getEmailAttachments()
   */
  public function getEmailAttachments(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $attachments = [];
    $files = $this->getTargetEntities($element, $webform_submission, $options) ?: [];
    foreach ($files as $file) {
      $attachments[] = [
        'filecontent' => '',
        'filename' => $file->getFilename(),
        'filemime' => $file->getMimeType(),
        // File URIs that are not supported return FALSE. When this happens,
        // use the file's URI as the file's path.
        'filepath' => $this->fileSystem->realpath($file->getFileUri()) ?: $file->getFileUri(),
        // URI is used when debugging or resending messages.
        // @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::buildAttachments
        '_fileurl' => $file->createFileUrl(FALSE),
      ];
    }
    return $attachments;
  }

}
