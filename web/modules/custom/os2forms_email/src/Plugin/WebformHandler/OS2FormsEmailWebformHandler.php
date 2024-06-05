<?php

namespace Drupal\os2forms_email\Plugin\WebformHandler;

use Drupal\file\Entity\File;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Emails a webform submission.
 *
 * @WebformHandler(
 *   id = "email",
 *   label = @Translation("OS2Forms email"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends a webform submission via an email."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   tokens = TRUE,
 * )
 */
class OS2FormsEmailWebformHandler extends EmailWebformHandler {

  /**
   * File element types.
   */
  private const FILE_ELEMENT_TYPES = [
    'webform_image_file',
    'webform_document_file',
    'webform_video_file',
    'webform_audio_file',
    'managed_file',
  ];

  /**
   * Sends extra notification based on attachment file size before sending message.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $message
   *   An array of message parameters.
   */
  public function sendMessage(WebformSubmissionInterface $webform_submission, array $message) {

    $webform = $webform_submission->getWebform();

    $settings = $webform->getThirdPartySetting('os2forms', 'os2forms_email');

    if ($settings['enabled'] && !empty($settings['emails'])) {
      $this->handleAttachmentNotification($webform_submission, $message, $settings['emails']);
    }

    return parent::sendMessage($webform_submission, $message);
  }

  /**
   * Handles attachment notification on submission.
   *
   * @param WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $message
   *   An array of message parameters.
   * @param string $emails
   *   A string of emails.
   *
   * @return void
   */
  private function handleAttachmentNotification(WebformSubmissionInterface $webform_submission, array $message, string $emails): void
  {
    if ($this->isAttachmentFileSizeTresholdSurpassed($webform_submission)) {
      $this->sendFileSizeNotification($webform_submission, $message, $emails);
    }
  }

  /**
   * Checks whether file size threshold is surpassed by submission.
   *
   * @param WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return bool
   */
  private function isAttachmentFileSizeTresholdSurpassed(WebformSubmissionInterface $webform_submission): bool
  {
    // Determine file size threshold in bytes
    $threshold = $this->configFactory->get('os2forms_email')->get('notification_file_size_threshold') ?? '2MB';
    $threshold = $this->convertToBytes($threshold);

    $fileElementIds = $this->getFileElementKeysFromSubmission($webform_submission);

    $totalSize = 0;
    foreach ($fileElementIds as $fileElementId) {
      $fileElement = File::load($fileElementId);
      $totalSize += (int) $fileElement->getSize();
    }

    return $totalSize > $threshold;
  }

  /**
   * Returns array of non-excluded file elements keys in submission.
   *
   * @param WebformSubmissionInterface $submission
   *   A webform submission.
   *
   * @return array
   */
  private function getFileElementKeysFromSubmission(WebformSubmissionInterface $submission): array {
    $elements = $submission->getWebform()->getElementsDecodedAndFlattened();

    // Removed excluded elements.
    $excludedElements = $this->configuration['excluded_elements'];

    foreach ($excludedElements as $key => $excludedElement) {
      if (array_key_exists($key, $elements)) {
        unset($elements[$key]);
      }
    }

    $fileElements = [];

    foreach (self::FILE_ELEMENT_TYPES as $fileElementType) {
      $fileElements[] = $this->getAvailableElementsByType($fileElementType, $elements);
    }

    // https://dev.to/klnjmm/never-use-arraymerge-in-a-for-loop-in-php-5go1
    $fileElements = array_merge(...$fileElements);

    $elementKeys = array_keys($fileElements);

    $fileIds = [];

    foreach ($elementKeys as $elementKey) {
      if (empty($submission->getData()[$elementKey])) {
        continue;
      }

      // Convert occurrences of singular file into array.
      $elementFileIds = (array) $submission->getData()[$elementKey];

      $fileIds[] = $elementFileIds;
    }

    return array_merge(...$fileIds);
  }


  /**
   * Get elements by type.
   *
   * @param string $type
   *   The type of elements wanted.
   * @param array $elements
   *    Array of elements.
   *
   * @return array
   */
  private function getAvailableElementsByType(string $type, array $elements): array {
    $attachmentElements = array_filter($elements, function ($element) use ($type) {
      return $type === $element['#type'];
    });

    return array_map(function ($element) {
      return $element['#title'];
    }, $attachmentElements);
  }

  /**
   * Converts threshold to bytes.
   *
   * @param string $threshold
   *
   * @return int
   */
  private function convertToBytes(string $threshold): int
  {
    $units = ['KB', 'MB', 'GB'];

    preg_match("/(?<num>\d+)(?<units>kb|mb|gb)$/i", $threshold, $matches);

    $size = (int) $matches['num'];
    $unit = strtoupper($matches['units']);

    $unitExponentMultiplier = (int) array_search($unit, $units);

    return $size * (1024 ** ($unitExponentMultiplier + 1));

  }

  /**
   * Sends file size notification message.
   *
   * @param WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $message
   *   An array of message parameters
   * @param string $emails
   *   A string of emails.
   *
   * @return void
   */
  private function sendFileSizeNotification(WebformSubmissionInterface $webform_submission, array $message, string $emails): void
  {
    $emails = explode(PHP_EOL, $emails);

    foreach ($emails as $emailAddress) {
      // Remove potential whitespace.
      $emailAddress = trim($emailAddress);

      $context = [
        '@form' => $this->getWebform()->label(),
        '@handler' => $this->label(),
        '@email' => $emailAddress,
        'link' => ($webform_submission->id()) ? $webform_submission->toLink($this->t('View'))->toString() : NULL,
        'webform_submission' => $webform_submission,
        'handler_id' => $this->getHandlerId(),
        'operation' => 'notification email',
      ];

      // Check if email is invalid.
      if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {

        if ($webform_submission->getWebform()->hasSubmissionLog()) {
          // Log detailed message to the 'webform_submission' log.
          $this->getLogger('webform_submission')->notice("Email notification advising surpassed file sizes could not be sent to '@email'.", $context);
        }

      } else {
        $notificationMessage = $this->defaultConfiguration();

        $notificationMessage['to_mail'] = $emailAddress;
        $notificationMessage['subject'] = $this->t('File size submission warning');

        $notificationMessage['body'] = $this->t(
          "<p>Dear @name</p><p>Submission @submission attempted sending an email with a large total file size of attachments surpassing @threshold for handler (@handler) on form (@form).</p>", [
          '@name' => $emailAddress,
          '@submission' => $context['link'],
          '@handler' => $context['@handler'],
          '@form' => $context['@form'] ?? '',
          '@threshold' => $this->configFactory->get('os2forms_email')->get('notification_file_size_threshold') ?? '2MB',
        ]);

        $notificationMessage['from_mail'] = $this->configFactory->get('os2forms_email')->get('notification_message_from_email');
        $notificationMessage['from_name'] = $this->configFactory->get('os2forms_email')->get('notification_message_from_name');
        $notificationMessage['webform_submission'] = $message['webform_submission'];
        $notificationMessage['handler'] = $message['handler'];

        $result = parent::sendMessage($webform_submission, $notificationMessage);

        if ($webform_submission->getWebform()->hasSubmissionLog() && $result) {
          // Log detailed message to the 'webform_submission' log.
          $this->getLogger('webform_submission')->notice("Email notification advising surpassed file sizes sent to '@email'.", $context);
        }

      }
    }
  }

}
