<?php

namespace Drupal\os2forms_failed_jobs\Form;

use Drupal\advancedqueue\Entity\QueueInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\Plugin\AdvancedQueue\Backend\Database;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\os2forms_failed_jobs\Helper\Helper;

/**
 * Provides a confirmation form for retrying a job.
 */
class RetryJob extends ConfirmFormBase {

  /**
   * The queue.
   *
   * @var \Drupal\advancedqueue\Entity\QueueInterface
   */
  protected QueueInterface $queue;

  /**
   * The job ID to release.
   *
   * @var int
   */
  protected int $jobId;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * Failed jobs helper.
   *
   * @var \Drupal\os2forms_failed_jobs\Helper\Helper
   */
  protected Helper $failedJobsHelper;

  /**
   * Retry job constructor.
   */
  public function __construct(Connection $database, EntityTypeManager $entityTypeManager, Helper $failedJobsHelper) {
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
    $this->failedJobsHelper = $failedJobsHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): RetryJob {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get(Helper::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'advancedqueue_retry_job';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to retry job @job_id from the @queue queue?', [
      '@job_id' => $this->jobId,
      '@queue' => $this->queue->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    $submissionId = $this->failedJobsHelper->getSubmissionIdFromJob($this->jobId);
    /** @var \Drupal\webform\WebformSubmissionInterface $submission */
    $submission = $this->entityTypeManager->getStorage('webform_submission')->load($submissionId);
    $webform = $submission->getWebform();

    return Url::fromRoute('entity.webform.error_log', ['webform' => $webform->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, QueueInterface $advancedqueue_queue = NULL, $job_id = NULL): array {
    $this->queue = $advancedqueue_queue;
    $this->jobId = $job_id;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $queue_backend = $this->queue->getBackend();
    if ($queue_backend instanceof Database) {
      $job = $this->failedJobsHelper->getJobFromId($this->jobId);

      if ($job->getState() != Job::STATE_FAILURE) {
        throw new \InvalidArgumentException('Only failed jobs can be retried.');
      }

      $queue_backend->retryJob($job);
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
  }

}
