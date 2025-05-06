<?php

namespace Drupal\os2forms_queued_email\Plugin\WebformElement;

use Drupal\os2forms_queued_email\Plugin\AdvancedQueue\JobType\QueuedEmail;
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
    $attachments = parent::getEmailAttachments($element, $webform_submission, $options);

    foreach ($attachments as &$attachment) {
      $attachment[QueuedEmail::FILECONTENT] = '';
      $attachment[QueuedEmail::OS2FORMS_QUEUED_EMAIL_IS_STATIC_FILE] = TRUE;
    }

    return $attachments;
  }

}
