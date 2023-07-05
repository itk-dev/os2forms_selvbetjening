<?php

namespace Drupal\os2forms_forloeb;

use DigitalPost\MeMo\Action;
use DigitalPost\MeMo\EntryPoint;
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
use Drupal\Core\Url;
use Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\Utility\TaskHandler;
use Drupal\os2forms_digital_post\Helper\DigitalPostHelper;
use Drupal\os2forms_digital_post\Model\Document;
use Drupal\os2forms_forloeb\Exception\RuntimeException;
use Drupal\os2forms_forloeb\Form\SettingsForm;
use Drupal\os2forms_forloeb\Plugin\AdvancedQueue\JobType\SendMeastroNotification;
use Drupal\os2forms_forloeb\Plugin\EngineTasks\MaestroWebformInheritTask;
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

  public const OS2FORMS_FORLOEB_NOTIFICATION_ASSIGNMENT = 'assignment';
  public const OS2FORMS_FORLOEB_NOTIFICATION_REMINDER = 'reminder';
  public const OS2FORMS_FORLOEB_NOTIFICATION_ESCALATION = 'escalation';

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
    readonly private EntityPrintPluginManagerInterface $entityPrintPluginManager,
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
    // @todo Clean up and align with MaestroWebformInheritTask::webformSubmissionFormAlter().
    $templateTask = MaestroEngine::getTemplateTaskByID($templateMachineName, $taskMachineName);
    if (MaestroWebformInheritTask::isWebformTask($templateTask)) {
      if ($inheritWebformUniqueId = ($templateTask['data'][MaestroWebformInheritTask::INHERIT_WEBFORM_UNIQUE_ID] ?? NULL)) {
        if ($processID = (MaestroEngine::getProcessIdFromQueueId($queueID) ?: NULL)) {
          if ($entityIdentifier = (self::getWebformSubmissionIdentifiersForProcess($processID)[$inheritWebformUniqueId] ?? NULL)) {
            $submission = $this->webformSubmissionStorage->load($entityIdentifier['entity_id']);
            if ($submission) {
              $this->handleSubmissionNotification($notificationType, $submission, $templateTask, $queueID);
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
    string $notificationType,
    WebformSubmissionInterface $submission,
    array $templateTask,
    int $maestroQueueID
  ): ?Job {
    $context = [
      'webform_submission' => $submission,
    ];

    try {
      $job = Job::create(SendMeastroNotification::class, [
        'notificationType' => $notificationType,
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
      'notificationType' => $notificationType,
      'templateTask' => $templateTask,
      'queueID' => $maestroQueueID,
      'submissionID' => $submissionID,
    ] = $payload;

    $submission = $this->webformSubmissionStorage->load($submissionID);

    $this->sendNotification($notificationType, $submission, $templateTask, $maestroQueueID);

    return JobResult::success();
  }

  /**
   * Send notification.
   */
  private function sendNotification(
    string $notificationType,
    WebformSubmissionInterface $submission,
    array $templateTask,
    int $maestroQueueID
  ) {
    $context = [
      'webform_submission' => $submission,
    ];

    try {
      $handlers = $submission->getWebform()->getHandlers();
      foreach ($handlers as $handler) {
        if (!($handler instanceof MaestroNotificationHandler)
        || $handler->isDisabled()
        || $handler->isExcluded()
        || !$handler->isNotificationEnabled($notificationType)
        ) {
          continue;
        }

        [
          'content' => $content,
          'contentType' => $contentType,
          'recipient' => $recipient,
          'subject' => $subject,
          'taskUrl' => $taskUrl,
          'actionLabel' => $actionLabel,
        ] = $this->renderNotification($submission, $handler->getHandlerId(), $notificationType, $templateTask, $maestroQueueID);

        if ('email' === $contentType) {
          $this->sendNotificationEmail($recipient, $subject, $content, $submission, $notificationType);
        }
        else {
          $this->sendNotificationDigitalPost($recipient, $subject, $content, $taskUrl, $actionLabel, $submission, $notificationType);
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
    string $body,
    WebformSubmissionInterface $submission,
    string $notificationType
  ): void {
    try {
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
        throw new RuntimeException(sprintf('Error sending notification (%s) email to %s', $notificationType, $recipient));
      }

      $this->notice('Email notification (@type) sent to @recipient', [
        '@type' => $notificationType,
        'webform_submission' => $submission,
        '@recipient' => $recipient,
        'handler_id' => 'os2forms_forloeb',
        'operation' => 'notification sent',
      ]);
    }
    catch (\Exception $exception) {
      $this->error('Error sending email notification (@type): @message', [
        '@type' => $notificationType,
        '@message' => $exception->getMessage(),
        'webform_submission' => $submission,
        'handler_id' => 'os2forms_forloeb',
        'operation' => 'failed sending notification',
      ]);
    }
  }

  /**
   * Send notification digital post.
   */
  private function sendNotificationDigitalPost(
    string $recipient,
    string $subject,
    string $content,
    string $taskUrl,
    string $actionLabel,
    WebformSubmissionInterface $submission,
    string $notificationType
  ): void {
    if (!$this->moduleHandler->moduleExists('os2forms_digital_post')) {
      throw new RuntimeException('Cannot send digital post. Module os2forms_digital_post not installed.');
    }

    try {
      $document = new Document(
        $content,
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
          ->setLabel($actionLabel),
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

      $this->notice('Digital post notification sent (@type)', [
        '@type' => $notificationType,
        'webform_submission' => $submission,
        'handler_id' => 'os2forms_forloeb',
        'operation' => 'notification sent',
      ]);
    }
    catch (\Exception $exception) {
      $this->error('Error sending digital post notification (@type): @message', [
        '@type' => $notificationType,
        '@message' => $exception->getMessage(),
        'webform_submission' => $submission,
        'handler_id' => 'os2forms_forloeb',
        'operation' => 'failed sending notification',
      ]);
    }
  }

  /**
   * Render notification.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The submission.
   * @param string $handlerId
   *   The handler ID.
   * @param string $notificationType
   *   The notification type.
   * @param array $templateTask
   *   The Maestro template task.
   * @param int $maestroQueueID
   *   The Maestro queue ID.
   * @param string|null $contentType
   *   Optional content type. If not set the content type will be compoted based
   *   on the recipient.
   *
   * @return array
   *   The rendered notification with keys
   *   - content
   *   - contentType
   *   - recipient
   *   - subject
   *   - taskUrl (for digital post)
   *   - actionLabel (for digital post)
   */
  public function renderNotification(WebformSubmissionInterface $submission, string $handlerId, string $notificationType, array $templateTask, int $maestroQueueID, string $contentType = NULL): array {
    $handler = $submission->getWebform()->getHandler($handlerId);
    $settings = $handler->getSettings();

    $data = $submission->getData();
    $recipientElement = $settings[MaestroNotificationHandler::NOTIFICATION][MaestroNotificationHandler::RECIPIENT_ELEMENT] ?? NULL;
    // Handle os2forms_person_lookup element.
    $recipient = $data[$recipientElement]['cpr_number']
      // Simple element.
      ?? $data[$recipientElement]
      ?? NULL;

    if ($notificationType === self::OS2FORMS_FORLOEB_NOTIFICATION_ESCALATION) {
      $recipient = $settings[MaestroNotificationHandler::NOTIFICATION][$notificationType][MaestroNotificationHandler::NOTIFICATION_RECIPIENT] ?? NULL;
    }

    if (NULL !== $recipient) {
      // Lifted from MaestroEngine.
      $maestroTokenData = [
        'maestro' => [
          'task' => $templateTask,
          'queueID' => $maestroQueueID,
        ],
      ];

      $notificationSetting = $settings[MaestroNotificationHandler::NOTIFICATION][$notificationType] ?? NULL;
      if (NULL === $notificationSetting) {
        throw new RuntimeException(sprintf('Cannot get setting for %s notification', $notificationType));
      }

      $processValue = static fn (string $value) => $value;

      // Handle a preview, i.e. not a real Maestro context.
      if (empty($templateTask) || 0 === $maestroQueueID) {
        $taskUrl = Url::fromRoute('os2forms_forloeb.meastro_notification.preview_message', ['message' => 'This is just a preview'])->toString(TRUE)->getGeneratedUrl();

        $processValue = static function (string $value) use ($taskUrl) {
          // Replace href="[maestro:task-url]" with href="«$taskUrl»".
          $value = preg_replace('/href\s*=\s*["\']\[maestro:task-url\]["\']/', sprintf('href="%s"', htmlspecialchars($taskUrl)), $value);
          $value = preg_replace('/\[(maestro:[^]]+)\]/', '&#91;\1&#93;', $value);

          return $value;
        };
      }
      else {
        $taskUrl = TaskHandler::getHandlerURL($maestroQueueID);
      }

      $subject = $this->tokenManager->replace(
        $processValue($notificationSetting[MaestroNotificationHandler::NOTIFICATION_SUBJECT]),
        $submission,
        $maestroTokenData
      );

      $content = $notificationSetting[MaestroNotificationHandler::NOTIFICATION_CONTENT];
      if (isset($content['value'])) {
        // Process tokens in content.
        $content['value'] = $this->tokenManager->replace(
          $processValue($content['value']),
          $submission,
          $maestroTokenData
        );
      }

      $actionLabel = $this->tokenManager->replace($notificationSetting[MaestroNotificationHandler::NOTIFICATION_ACTION_LABEL], $submission);

      if (NULL === $contentType) {
        $contentType = filter_var($recipient, FILTER_VALIDATE_EMAIL) ? 'email' : 'pdf';
      }

      switch ($contentType) {
        case 'email':
          $content = $this->renderHtml($contentType, $subject, $content, $taskUrl, $actionLabel, $submission);
          break;

        case 'pdf':
          $pdfContent = $this->renderHtml($contentType, $subject, $content, $taskUrl, $actionLabel, $submission);

          // Get dompdf plugin from entity_print module.
          /** @var \Drupal\entity_print\Plugin\EntityPrint\PrintEngine\PdfEngineBase $printer */
          $printer = $this->entityPrintPluginManager->createInstance('dompdf');
          $printer->addPage($pdfContent);
          $content = $printer->getBlob();
          break;

        default:
          throw new RuntimeException(sprintf('Invalid content type: %s', $contentType));
      }

      return [
        'content' => $content,
        'contentType' => $contentType,
        'recipient' => $recipient,
        'subject' => $subject,
        'taskUrl' => $taskUrl,
        'actionLabel' => $actionLabel,
      ];
    }

    throw new RuntimeException();
  }

  /**
   * Build HTML.
   */
  private function renderHtml(
    string $type,
    string $subject,
    array $content,
    string $taskUrl,
    string $actionLabel,
    WebformSubmissionInterface $submission
  ): string|MarkupInterface {
    $template = $this->config->get('templates')['notification_' . $type] ?? NULL;
    if (file_exists($template)) {
      $template = file_get_contents($template) ?: NULL;
    }
    if (NULL === $template) {
      $template = 'Missing or invalid template';
    }

    $build = [
      '#type' => 'inline_template',
      '#template' => $template,
      '#context' => [
        'message' => [
          'subject' => $subject,
          'content' => $content,
        ],
        'task_url' => $taskUrl,
        'action_label' => $actionLabel,
        'webform_submission' => $submission,
        'handler' => $this,
      ],
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
