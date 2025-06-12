<?php

namespace Drupal\os2forms_queued_email\Plugin\AdvancedQueue\JobType;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\os2web_audit\Service\Logger;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queued email job.
 *
 * @AdvancedQueueJobType(
 *   id = "Drupal\os2forms_queued_email\Plugin\AdvancedQueue\JobType\QueuedEmail",
 *   label = @Translation("Queued Email"),
 * )
 */
final class QueuedEmail extends JobTypeBase implements ContainerFactoryPluginInterface {

  public const OS2FORMS_QUEUED_EMAIL_LOGGER_CHANNEL = 'os2forms_queued_email_info';
  public const OS2FORMS_QUEUED_EMAIL_IS_STATIC_FILE = 'OS2FORMS_QUEUED_EMAIL_IS_STATIC_FILE';
  public const OS2FORMS_QUEUED_EMAIL_FILE_PATH = 'private://queued-email-files';
  public const OS2FORMS_QUEUED_EMAIL_CONFIG_NAME = 'os2forms_queued_email_file_path';
  public const FILECONTENT = 'filecontent';
  public const FILEPATH = 'filepath';

  /**
   * The submission logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $submissionLogger;

  /**
   * The submission logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $queuedEmailLogger;

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly MailManagerInterface $mailManager,
    private readonly Logger $auditLogger,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->submissionLogger = $loggerFactory->get('webform_submission');
    $this->queuedEmailLogger = $loggerFactory->get(self::OS2FORMS_QUEUED_EMAIL_LOGGER_CHANNEL);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.mail'),
      $container->get('os2web_audit.logger'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Processes the ArchiveDocument job.
   */
  public function process(Job $job): JobResult {
    try {
      $payload = $job->getPayload();
      $message = json_decode($payload['message'], TRUE);

      // Gather filenames for os2forms attachments for deletion later.
      $os2formsAttachmentFilenames = [];

      // Handle potential attachments.
      foreach ($message['params']['attachments'] as &$attachment) {
        // Handle OS2Forms attachments.
        if (isset($attachment[self::OS2FORMS_QUEUED_EMAIL_CONFIG_NAME])) {
          $os2formsAttachmentFilenames[] = $attachment[self::OS2FORMS_QUEUED_EMAIL_CONFIG_NAME];

          if (!file_exists($attachment[self::OS2FORMS_QUEUED_EMAIL_CONFIG_NAME])) {
            throw new \Exception('OS2Forms attachment file not found: ' . $attachment[self::OS2FORMS_QUEUED_EMAIL_CONFIG_NAME]);
          }

          $attachment[self::FILECONTENT] = file_get_contents($attachment[self::OS2FORMS_QUEUED_EMAIL_CONFIG_NAME]);

          if (FALSE === $attachment[self::FILECONTENT]) {
            throw new \Exception('OS2Forms attachment file cannot be read: ' . $attachment[self::OS2FORMS_QUEUED_EMAIL_CONFIG_NAME]);
          }

          unset($attachment[self::OS2FORMS_QUEUED_EMAIL_CONFIG_NAME]);
        }
        else {
          // Handle normal file attachments.
          if ($attachment[self::FILECONTENT] === '') {
            if (isset($attachment[self::FILEPATH]) && file_exists($attachment[self::FILEPATH])) {
              $attachment[self::FILECONTENT] = file_get_contents($attachment[self::FILEPATH]);
            }
            else {
              throw new \Exception('File not found: ' . $attachment[self::FILEPATH]);
            }
          }
        }

      }

      $result = $this->mailManager->createInstance('SMTPMailSystem')->mail($message);

      // Logging of failed mail is handled in catch.
      if (!$result) {
        throw new \Exception('Failed sending email');
      }

      try {
        // Load the Webform submission entity by ID.
        // Beware that some webforms may be configured to NOT save submissions,
        // submission may therefore be null.
        $submission = WebformSubmission::load($payload['submissionId']);

        if ($submission) {
          $logger_context = [
            'handler_id' => 'os2forms_queued_email',
            'channel' => 'webform_submission',
            'webform_submission' => $submission,
            'operation' => 'email sent',
          ];

          $this->submissionLogger->notice($this->t('The submission #@serial was successfully delivered', ['@serial' => $submission->serial()]), $logger_context);
        }

      }
      catch (\Exception $e) {
        $this->queuedEmailLogger->notice(sprintf('Failed logging to webform_submission logger: %s', $e->getMessage()));
      }

      // Remove OS2Forms attachments.
      foreach ($os2formsAttachmentFilenames as $os2formsAttachmentFilename) {
        unlink($os2formsAttachmentFilename);
      }

      $msg = sprintf('Email, %s, sent to %s. Webform id: %s.', $payload['subject'] ?? NULL, $payload['to'] ?? NULL, $payload['webformId'] ?? NULL);
      $this->auditLogger->info('Email', $msg);

      return JobResult::success();
    }
    catch (\Exception $e) {

      try {
        $submission = WebformSubmission::load($payload['submissionId']);

        if ($submission) {
          $logger_context = [
            'handler_id' => 'os2forms_queued_email',
            'channel' => 'webform_submission',
            'webform_submission' => $submission,
            'operation' => 'email failed',
          ];

          $this->submissionLogger->error($this->t('The submission #@serial failed (@message)', [
            '@serial' => $submission->serial(),
            '@message' => $e->getMessage(),
          ]), $logger_context);
        }

      }
      catch (\Exception $e) {
        $this->queuedEmailLogger->notice(sprintf('Failed logging to webform_submission logger: %s', $e->getMessage()));
      }

      return JobResult::failure($e->getMessage());
    }
  }

}
