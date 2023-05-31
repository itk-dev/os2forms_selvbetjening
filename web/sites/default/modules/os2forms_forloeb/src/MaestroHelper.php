<?php

namespace Drupal\os2forms_forloeb;

use Dompdf\Dompdf;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\Utility\TaskHandler;
use Drupal\os2forms_forloeb\Plugin\EngineTasks\MaestroWebformInheritTask;
use Drupal\os2forms_forloeb\Form\SettingsForm;
use Drupal\os2forms_forloeb\Plugin\WebformHandler\NotificationHandler;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Drupal\webform\WebformThemeManagerInterface;
use Drupal\webform\WebformTokenManagerInterface;

/**
 * Maestro helper.
 */
class MaestroHelper {
  private const OS2FORMS_FORLOEB_IS_NOTIFICATION = 'os2forms_forloeb_is_notification';
  private const OS2FORMS_FORLOEB_NOTIFICATION_CONTENT = 'os2forms_forloeb_notification_content';
  private const OS2FORMS_FORLOEB_NOTIFICATION_ASSIGNMENT = 'assignment';
  private const OS2FORMS_FORLOEB_NOTIFICATION_REMINDER = 'reminder';
  private const OS2FORMS_FORLOEB_NOTIFICATION_ESCALATION = 'escalation';

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  readonly private ImmutableConfig $config;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface|\Drupal\Core\Entity\EntityStorageInterface
   */
  readonly private WebformSubmissionStorageInterface $webformSubmissionStorage;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
    readonly private WebformTokenManagerInterface $tokenManager,
    readonly private MailManagerInterface $mailManager,
    readonly private LanguageManagerInterface $languageManager,
    readonly private WebformThemeManagerInterface $webformThemeManager
  ) {
    $this->config = $configFactory->get(SettingsForm::SETTINGS);
    $this->webformSubmissionStorage = $entityTypeManager->getStorage('webform_submission');
  }

  /**
   * Implements hook_maestro_zero_user_notification().
   */
  public function maestroZeroUserNotification($templateMachineName, $taskMachineName, $queueID, $notificationType) {
    if (self::OS2FORMS_FORLOEB_NOTIFICATION_ASSIGNMENT === $notificationType) {
      // @todo Clean up and align with MaestroWebformInheritTask::webformSubmissionFormAlter().
      $templateTask = MaestroEngine::getTemplateTaskByID($templateMachineName, $taskMachineName);
      if (MaestroWebformInheritTask::isWebformTask($templateTask)) {
        if ($inheritWebformUniqueId = ($templateTask['data'][MaestroWebformInheritTask::INHERIT_WEBFORM_UNIQUE_ID] ?? NULL)) {
          if ($processID = (MaestroEngine::getProcessIdFromQueueId($queueID) ?: NULL)) {
            if ($entityIdentifier = (self::getWebformSubmissionIdentifiersForProcess($processID)[$inheritWebformUniqueId] ?? NULL)) {
              $submission = $this->webformSubmissionStorage->load($entityIdentifier['entity_id']);
              if ($submission) {
                $this->handleSubmissionNotification($submission, $templateTask, $queueID);
              }
            }
          }
        }
      }
    }
  }

  /**
   * Get webform submission identifiers for a process.
   *
   * @param int $processID
   *   The Maestro Process ID.
   *
   * @return array
   *   The webform submission identifiers sorted ascendingly by creation time.
   */
  public static function getWebformSubmissionIdentifiersForProcess(int $processID): array {
    // Get webform submissions in process.
    $entityIdentifiers = array_filter(
      MaestroEngine::getAllEntityIdentifiersForProcess($processID),
      static fn (array $entityIdentifier) => 'webform_submission' === ($entityIdentifier['entity_type'] ?? NULL)
    );

    // Sort by entity ID.
    uasort($entityIdentifiers, static fn (array $a, array $b) => ($b['entity_id'] ?? 0) <=> ($a['entity_id'] ?? 0));

    return $entityIdentifiers;
  }

  /**
   * Implements hook_maestro_can_user_execute_task_alter().
   */
  public function maestroCanUserExecuteTaskAlter(bool &$returnValue, int $queueID, int $userID): void {
    // Perform our checks only if an anonymous user has been barred access.
    if (0 === $userID && FALSE === $returnValue) {
      $templateTask = MaestroEngine::getTemplateTaskByQueueID($queueID);
      if (isset($templateTask['assigned'])) {
        $assignments = explode(',', $templateTask['assigned']);

        // Check if one of the assignments match our known anonymous roles.
        $knownAnonymousAssignments = array_map(
          static fn(string $role) => 'role:fixed:' . $role,
          array_filter($this->config->get('known_anonymous_roles') ?: [])
        );

        foreach ($assignments as $assignment) {
          if (in_array($assignment, $knownAnonymousAssignments, TRUE)) {
            $returnValue = TRUE;
          }
        }
      }
    }
  }

  /**
   * Handle submission notification.
   */
  private function handleSubmissionNotification(
    WebformSubmissionInterface $submission,
    array $templateTask,
    int $queueID
  ): void {
    $data = $submission->getData();
    $webform = $submission->getWebform();
    $handlers = $webform->getHandlers('os2forms_forloeb_notification');
    foreach ($handlers as $handler) {
      if ($handler->isDisabled() || $handler->isExcluded()) {
        continue;
      }
      $settings = $handler->getSettings();
      $notificationSetting = $settings[NotificationHandler::NOTIFICATION];
      $recipientElement = $notificationSetting[NotificationHandler::RECIPIENT_ELEMENT] ?? NULL;
      $recipient =
        // Handle os2forms_person_lookup element.
        $data[$recipientElement]['cpr_number']
        // Simple element.
        ?? $data[$recipientElement]
        ?? NULL;
      if (NULL !== $recipient) {
        // Lifted from MaestroEngine.
        $maestroTokenData = [
          'maestro' => [
            'task' => $templateTask,
            'queueID' => $queueID,
          ],
        ];

        $subject = $this->tokenManager->replace(
          $notificationSetting[NotificationHandler::NOTIFICATION_SUBJECT],
          $submission,
          $maestroTokenData
        );

        $content = $notificationSetting[NotificationHandler::NOTIFICATION_CONTENT];
        if (isset($content['value'])) {
          // Process tokens in content.
          $content['value'] = $this->tokenManager->replace(
            $content['value'],
            $submission,
            $maestroTokenData
          );
        }

        $taskUrl = TaskHandler::getHandlerURL($queueID);
        $actionLabel = $notificationSetting[NotificationHandler::NOTIFICATION_ACTION_LABEL];

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
          $this->sendNotificationEmail($recipient, $subject, $content, $taskUrl, $actionLabel, $submission);
        }
        else {
          $this->sendNotificationDigitalPost($recipient, $subject, $content, $taskUrl, $actionLabel, $submission);
        }
      }
    }
  }

  /**
   * Send notification email.
   */
  private function sendNotificationEmail(
    string $recipient,
    string $subject,
    array $content,
    string $taskUrl,
    string $actionLabel,
    WebformSubmissionInterface $submission
  ): void {
    $body = $this->buildHtml('os2forms_forloeb_notification_message_email_html', $subject, $content, $taskUrl, $actionLabel, $submission);

    $message = [
      'subject' => $subject,
      'body' => $body,
      'html' => TRUE,
    ];

    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    $result = $this->mailManager->mail(
      'os2forms_forloeb',
      'notification',
      $recipient,
      $langcode,
      $message
    );

    if (!$result['result']) {
      // @todo Log this error.
    }
  }

  /**
   * Send notification digital post.
   */
  private function sendNotificationDigitalPost(
    string $recipient,
    string $subject,
    array $content,
    string $taskUrl,
    string $actionLabel,
    WebformSubmissionInterface $submission
  ): void {
    $pdfBody = $this->buildHtml('os2forms_forloeb_notification_message_pdf_html', $subject, $content, $taskUrl, $actionLabel, $submission);
    $dompdf = new Dompdf();
    $dompdf->loadHtml($pdfBody);
    $dompdf->render();
    $pdfContent = $dompdf->output();

    // @todo Send real digital post
    $recipient .= '@digital-post.example.com';
    $subject .= ' (digital post)';

    $body = $this->buildHtml('os2forms_forloeb_notification_message_email_html', $subject, $content, $taskUrl, $actionLabel, $submission);

    $message = [
      'subject' => $subject,
      'body' => $body,
      'html' => TRUE,
      'attachments' => [
        [
          'filecontent' => $pdfContent,
          'filename' => $subject . '.pdf',
          'filemime' => 'application/pdf',
        ],
      ],
    ];

    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    $result = $this->mailManager->mail(
      'os2forms_forloeb',
      'notification',
      $recipient,
      $langcode,
      $message
    );

    if (!$result['result']) {
      // @todo Log this error.
    }
  }

  /**
   * Build HTML.
   */
  private function buildHtml(
    string $theme,
    string $subject,
    array $content,
    string $taskUrl,
    string $actionLabel,
    WebformSubmissionInterface $submission
  ): string|MarkupInterface {
    // Render body as HTML.
    $build = [
      '#theme' => $theme,
      '#message' => [
        'subject' => $subject,
        'content' => $content,
      ],
      '#task_url' => $taskUrl,
      '#action_label' => $actionLabel,
      '#webform_submission' => $submission,
      '#handler' => $this,
    ];

    return Markup::create(trim((string) $this->webformThemeManager->render($build)));

  }

  /**
   * Implements hook_mail().
   */
  public function mail(string $key, array &$message, array $params) {
    switch ($key) {
      case 'notification':
        $message['subject'] = $params['subject'];
        $message['body'][] = $params['body'];
        if (isset($params['attachments'])) {
          foreach ($params['attachments'] as $attachment) {
            $message['params']['attachments'][] = $attachment;
          }
        }
        break;
    }
  }

  /**
   * Implements hook_mail_alter().
   */
  public function mailAlter(array &$message) {
    if (str_starts_with($message['id'], 'os2forms_forloeb')) {
      if (isset($message['params']['html']) && $message['params']['html']) {
        $message['headers']['Content-Type'] = 'text/html; charset=UTF-8; format=flowed';
      }
    }
  }

}
