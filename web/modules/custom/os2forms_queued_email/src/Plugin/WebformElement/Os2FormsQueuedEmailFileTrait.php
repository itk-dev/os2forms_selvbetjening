<?php

namespace Drupal\os2forms_queued_email\Plugin\WebformElement;

use Drupal\os2forms_queued_email\Plugin\AdvancedQueue\JobType\QueuedEmail;
use Drupal\webform\WebformSubmissionInterface;

/**
 * OS2Forms queued email file trait.
 */
trait Os2FormsQueuedEmailFileTrait {

  /**
   * {@inheritdoc}
   *
   * Removes file content and adds static file flag.
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
