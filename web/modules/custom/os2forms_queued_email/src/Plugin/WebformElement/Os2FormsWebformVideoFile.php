<?php

namespace Drupal\os2forms_queued_email\Plugin\WebformElement;

use Drupal\os2forms_queued_email\Plugin\AdvancedQueue\JobType\QueuedEmail;
use Drupal\webform\Plugin\WebformElement\WebformVideoFile;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Overrides 'webform_video_file' element.
 *
 * @WebformElement(
 *   id = "os2forms_webform_video_file",
 *   label = @Translation("Video file"),
 *   description = @Translation("Provides a form element for uploading and saving a video file."),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 *   dependencies = {
 *     "file",
 *   }
 * )
 */
class Os2FormsWebformVideoFile extends WebformVideoFile {

  /**
   * {@inheritdoc}
   *
   * OS2Forms webform video file element.
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
