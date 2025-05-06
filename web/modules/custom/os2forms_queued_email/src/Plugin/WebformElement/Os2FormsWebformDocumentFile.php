<?php

namespace Drupal\os2forms_queued_email\Plugin\WebformElement;

use Drupal\os2forms_queued_email\Plugin\AdvancedQueue\JobType\QueuedEmail;
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
   * OS2Forms webform document file element.
   *
   * Copied from WebformManagedFileBase::getEmailAttachments.
   * Only change is that filecontent is not passed on.
   *
   * @see parent::getEmailAttachments()
   */
  public function getEmailAttachments(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $attachments = parent::getEmailAttachments($element, $webform_submission, $options);

    foreach ($attachments as &$attachment) {
      $attachment[QueuedEmail::FILECONTENT] = '';
      $attachment[QueuedEmail::OS2FORMS_QUEUED_EMAIL_IS_STATIC_FILE] = TRUE;
    }

    return $attachments;
  }

}
