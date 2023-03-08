<?php

namespace Drupal\os2forms_queue_submission_relation\Helper;

use Drupal\advancedqueue\Event\JobEvent;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\webform\WebformSubmissionInterface;

/**
 * The helper class for os2forms_queue_submission_relation module.
 */
class Helper {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected LoggerChannelFactory $loggerFactory;

  /**
   * The helper service constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   The logger factory.
   */
  public function __construct(EntityTypeManager $entityTypeManager, Connection $database, LoggerChannelFactory $logger_factory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Handle a job from the AdvancedQueueProcessSubscriber.
   *
   * @param \Drupal\advancedqueue\Event\JobEvent $event
   *   The job about to be processed.
   */
  public function handleJob(JobEvent $event): void {
    $data = $this->getDataFromPayload($event->getJob()->getPayload());
    if ($data) {
      $data['job_id'] = (int) $event->getJob()->getId();
      $this->updateRelation($data);
    }
  }

  /**
   * Handle importing advanced queue jobs through a command.
   */
  public function handleImport(): void {
    $jobs = $this->getAllQueueJobs();
    foreach ($jobs as $job) {
      $payload = json_decode($job->payload, TRUE);
      $data = $this->getDataFromPayload($payload);
      if ($data) {
        $data['job_id'] = (int) $job->job_id;
        $this->updateRelation($data);
      }
    }
  }

  /**
   * Retrieve data from advanced queue job.
   *
   * @param array $payload
   *   The payload from an advanced queue job.
   *
   * @return array|null
   *   An array containing submission id and webform id.
   */
  private function getDataFromPayload(array $payload): ?array {
    $submissionId = $this->getSubmissionId($payload);
    if (empty($submissionId)) {
      return NULL;
    }

    try {
      return [
        'submission_id' => $submissionId,
        'webform_id' => $this->getWebformSubmission($submissionId)
        ?->getWebform()
        ?->id(),
      ];
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return NULL;
    }
  }

  /**
   * Add or update an advanced queue and submission relation.
   *
   * @param array $data
   *   An array of data to put into os2forms_queue_submission_relation table.
   */
  private function updateRelation(array $data): void {
    if (empty($data['job_id']) || empty($data['submission_id'])) {
      return;
    }

    try {
      $this->database->upsert('os2forms_queue_submission_relation')
        ->key('job_id')
        ->fields($data)
        ->execute();
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('os2forms_queue_submission_relation')
        ->error(
          'Error adding releation: %message', ['%message' => $e->getMessage()]);
    }
  }

  /**
   * Get all entries from the advancedqueue table.
   *
   * @return array
   *   A list of all entries from the advanced queue table.
   */
  private function getAllQueueJobs(): array {
    $query = $this->database->select('advancedqueue', 'q');
    $query->fields('q');

    return $query->execute()->fetchAll();
  }

  /**
   * Get submission id from advanced queue job payload.
   *
   * @param array $payload
   *   The payload of an advanced queue job.
   *
   * @return int|null
   *   A webform submission id.
   */
  private function getSubmissionId(array $payload): ?int {
    return $payload['submissionId'] ?? $payload['submission']['id'] ?? NULL;
  }

  /**
   * Get webform id from submission.
   *
   * @param int $submissionId
   *   Id of a submission.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   A webform id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getWebformSubmission(int $submissionId): WebformSubmissionInterface {
    return $this->entityTypeManager->getStorage('webform_submission')->load($submissionId);
  }

}
