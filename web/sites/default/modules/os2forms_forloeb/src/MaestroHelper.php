<?php

namespace Drupal\os2forms_forloeb;

use DigitalPost\MeMo\Action;
use DigitalPost\MeMo\EntryPoint;
use Dompdf\Dompdf;
use Drupal\advancedqueue\Entity\QueueInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\Utility\TaskHandler;
use Drupal\os2forms_digital_post\Helper\DigitalPostHelper;
use Drupal\os2forms_digital_post\Model\Document;
use Drupal\os2forms_forloeb\Exception\RuntimeException;
use Drupal\os2forms_forloeb\Plugin\AdvancedQueue\JobType\SendMeastroNotification;
use Drupal\os2forms_forloeb\Plugin\EngineTasks\MaestroWebformInheritTask;
use Drupal\os2forms_forloeb\Form\SettingsForm;
use Drupal\os2forms_forloeb\Plugin\WebformHandler\MaestroNotificationHandler;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Drupal\webform\WebformThemeManagerInterface;
use Drupal\webform\WebformTokenManagerInterface;
use ItkDev\Serviceplatformen\Service\SF1601\SF1601;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Maestro helper.
 */
class MaestroHelper implements LoggerInterface {
  use LoggerTrait;

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
   * The queue storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  readonly private EntityStorageInterface $queueStorage;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
    readonly private WebformTokenManagerInterface $tokenManager,
    readonly private MailManagerInterface $mailManager,
    readonly private LanguageManagerInterface $languageManager,
    readonly private WebformThemeManagerInterface $webformThemeManager,
    readonly private LoggerChannelInterface $logger,
    readonly private LoggerChannelInterface $submissionLogger,
    readonly private ModuleHandlerInterface $moduleHandler,
    readonly private DigitalPostHelper $digitalPostHelper
  ) {
    $this->config = $configFactory->get(SettingsForm::SETTINGS);
    $this->webformSubmissionStorage = $entityTypeManager->getStorage('webform_submission');
    $this->queueStorage = $entityTypeManager->getStorage('advancedqueue_queue');
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
   * Handle submission notification.
   */
  private function handleSubmissionNotification(
    WebformSubmissionInterface $submission,
    array $templateTask,
    int $maestroQueueID
  ): ?Job {
    $context = [
      'webform_submission' => $submission,
    ];

    try {
      $job = Job::create(SendMeastroNotification::class, [
        'templateTask' => $templateTask,
        'queueID' => $maestroQueueID,
        'submissionID' => $submission->id(),
        'webformID' => $submission->getWebform()->id(),
      ]);

      $queue = $this->loadQueue();
      $queue->enqueueJob($job);
      $context['@queue'] = $queue->id();
      $this->notice('Job for sending notification added to the queue @queue.', $context + [
        'handler_id' => 'os2forms_forloeb',
        'operation' => 'notification queued for sending',
      ]);

      return $job;
    }
    catch (\Exception $exception) {
      $this->error('Error creating job for sending notification: @message', $context + [
        '@message' => $exception->getMessage(),
        'handler_id' => 'os2forms_forloeb',
        'operation' => 'notification failed',
        'exception' => $exception,
      ]);

      return NULL;
    }
  }

  /**
   * Process a job.
   */
  public function processJob(Job $job): JobResult {
    $payload = $job->getPayload();
    [
      'templateTask' => $templateTask,
      'queueID' => $maestroQueueID,
      'submissionID' => $submissionID,
    ] = $payload;

    $submission = $this->webformSubmissionStorage->load($submissionID);

    $this->sendNotification($submission, $templateTask, $maestroQueueID);

    return JobResult::success();
  }

  /**
   * Send notification.
   */
  private function sendNotification(
    WebformSubmissionInterface $submission,
    array $templateTask,
    int $maestroQueueID
  ) {
    $context = [
      'webform_submission' => $submission,
    ];

    try {
      $data = $submission->getData();
      $webform = $submission->getWebform();
      $handlers = $webform->getHandlers();
      foreach ($handlers as $handler) {
        if (!($handler instanceof MaestroNotificationHandler) || $handler->isDisabled() || $handler->isExcluded()) {
          continue;
        }
        $settings = $handler->getSettings();
        $notificationSetting = $settings[MaestroNotificationHandler::NOTIFICATION];
        $recipientElement = $notificationSetting[MaestroNotificationHandler::RECIPIENT_ELEMENT] ?? NULL;
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
              'queueID' => $maestroQueueID,
            ],
          ];

          $subject = $this->tokenManager->replace(
            $notificationSetting[MaestroNotificationHandler::NOTIFICATION_SUBJECT],
            $submission,
            $maestroTokenData
          );

          $content = $notificationSetting[MaestroNotificationHandler::NOTIFICATION_CONTENT];
          if (isset($content['value'])) {
            // Process tokens in content.
            $content['value'] = $this->tokenManager->replace(
              $content['value'],
              $submission,
              $maestroTokenData
            );
          }

          $taskUrl = TaskHandler::getHandlerURL($maestroQueueID);
          $actionLabel = $notificationSetting[MaestroNotificationHandler::NOTIFICATION_ACTION_LABEL];

          if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->sendNotificationEmail($recipient, $subject, $content, $taskUrl, $actionLabel, $submission);
          }
          else {
            $this->sendNotificationDigitalPost($recipient, $subject, $content, $taskUrl, $actionLabel, $submission);
          }
        }
      }
    }
    catch (\Exception $exception) {
      $this->error('Error sending notification: @message', $context + [
        '@message' => $exception->getMessage(),
        'handler_id' => 'os2forms_forloeb',
        'operation' => 'notification failed',
        'exception' => $exception,
      ]);

      return NULL;
    }
  }

  /**
   * Load advanced queue if any.
   *
   * @return \Drupal\advancedqueue\Entity\QueueInterface
   *   The queue.
   */
  private function loadQueue(): QueueInterface {
    $queueId = $this->config->get('processing')['queue'] ?? NULL;

    if (NULL === $queueId) {
      throw new RuntimeException('Cannot get queue ID');
    }

    $queue = $this->queueStorage->load($queueId);
    if (NULL === $queue) {
      throw new RuntimeException(sprintf('Cannot load queue %s', $queueId));
    }

    return $queue;
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
      throw new RuntimeException(sprintf('Error sending notification email to %s', $recipient));
    }

    $this->notice('Notification email sent to @recipient', [
      'webform_submission' => $submission,
      '@recipient' => $recipient,
      'handler_id' => 'os2forms_forloeb',
      'operation' => 'notification sent',
    ]);
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
  ): void
  {
    if (!$this->moduleHandler->moduleExists('os2forms_digital_post')) {
      throw new RuntimeException('Cannot send digital post. Module os2forms_digital_post not installed.');
    }

    try {
      $pdfBody = $this->buildHtml('os2forms_forloeb_notification_message_pdf_html', $subject, $content, $taskUrl,
        $actionLabel, $submission);
      $dompdf = new Dompdf();
      $dompdf->loadHtml($pdfBody);
      $dompdf->render();
      $pdfContent = $dompdf->output();

      $document = new Document(
        $pdfContent,
        Document::MIME_TYPE_PDF,
        $subject . '.pdf'
      );

      $senderLabel = $subject;
      $messageLabel = $subject;

      $recipientLookupResult = $this->digitalPostHelper->lookupRecipient($recipient);
      $actions = [
        (new Action())
          ->setActionCode(SF1601::ACTION_SELVBETJENING)
          ->setEntryPoint((new EntryPoint())
            ->setUrl($taskUrl)
          )
          ->setLabel($actionLabel)
      ];

      $message = $this->digitalPostHelper->getMeMoHelper()->buildMessage($recipientLookupResult, $senderLabel,
        $messageLabel, $document, $actions);
      $forsendelse = $this->digitalPostHelper->getForsendelseHelper()->buildForsendelse($recipientLookupResult,
        $messageLabel, $document);
      $this->digitalPostHelper->sendDigitalPost(
        SF1601::TYPE_AUTOMATISK_VALG,
        $message,
        $forsendelse,
        $submission
      );

      $this->notice('Digital post sent', [
        'webform_submission' => $submission,
        'handler_id' => 'os2forms_forloeb',
        'operation' => 'notification sent',
      ]);
    } catch (\Exception $exception) {
      $this->error('Error sending digital post: @message', [
        '@message' => $exception->getMessage(),
        'webform_submission' => $submission,
        'handler_id' => 'os2forms_forloeb',
        'operation' => 'failed sending notification',
      ]);
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

    return Markup::create(trim((string) $this->webformThemeManager->renderPlain($build)));
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

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []): void {
    $this->logger->log($level, $message, $context);
    // @see https://www.drupal.org/node/3020595
    if (isset($context['webform_submission']) && $context['webform_submission'] instanceof WebformSubmissionInterface) {
      $this->submissionLogger->log($level, $message, $context);
    }
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

}
