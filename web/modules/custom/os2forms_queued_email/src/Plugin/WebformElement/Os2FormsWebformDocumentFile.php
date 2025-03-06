<?php

namespace Drupal\os2forms_queued_email\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformDocumentFile;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Overrides 'webform_document_file' element.
 *
 * @WebformElement(
 *   id = "os2forms_webform_document_file",
 *   label = @Translation("Document file"),
 *   description = @Translation("Provides a form element for uploading and saving a document."),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 *   dependencies = {
 *     "file",
 *   }
 * )
 */
class Os2FormsWebformDocumentFile extends WebformDocumentFile {

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
        // File URIs that are not supported return FALSE, when this happens
        // still use the file's URI as the file's path.
        'filepath' => $this->fileSystem->realpath($file->getFileUri()) ?: $file->getFileUri(),
        // URI is used when debugging or resending messages.
        // @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::buildAttachments
        '_fileurl' => $file->createFileUrl(FALSE),
      ];
    }
    return $attachments;
  }

}
