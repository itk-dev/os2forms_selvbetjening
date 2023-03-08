<?php

namespace Drupal\os2forms_queue_submission_relation\Helper;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;

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
   * @param EntityTypeManager $entityTypeManager
   * @param Connection $database
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   */
  public function __construct(EntityTypeManager $entityTypeManager, Connection $database, LoggerChannelFactory $logger_factory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Retrieve data from advanced queue job.
   *
   * @param array $payload
   *   The payload from an advanced queue job
   *
   * @return array
   *   An array containing submission id and webform id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDataFromPayload(array $payload): array {
    $submissionId = $this->getSubmissionId($payload);

    return [
      'submission_id' => (int)$submissionId,
      'webform_id' => $this->getWebformId($submissionId)
    ];
  }

  /**
   * Add or update an advanced queue and submission relation.
   *
   * @param array $data
   *   An array of data to put into os2forms_queue_submission_relation table.
   *
   * @return void
   */
  public function addUpdateRelation(array $data) {
    if (empty($data['job_id']) || empty($data['submission_id'])) {
      return;
    }

    try {
      $result = $this->database->upsert('os2forms_queue_submission_relation')
        ->key('job_id')
        ->fields($data)
        ->execute();
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('os2forms_queue_submission_relation')->error($e);
    }
  }

  /**
   * Get all entries from the advancedqueue table.
   *
   * @return array
   */
  public function getAllQueueJobs(): array {
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
   * @return string
   *   A webform submission id.
   */
  private function getSubmissionId(array $payload): string {
    return $payload['submissionId'] ?? $payload['submission']['id'];
  }

  /**
   * Get webform id from submission.
   *
   * @param string $submissionId
   *   Id of a submission.
   *
   * @return string|null
   *   A webform id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getWebformId(string $submissionId): ?string {
    /** @var \Drupal\webform\WebformSubmissionInterface $submission */
    $submission = $this->entityTypeManager->getStorage('webform_submission')->load($submissionId);

    // For some reason phpstan insists that submission cannot be NULL but that
    // doesnt match the load method documentation.
    /** @phpstan-ignore-next-line */
    return !empty($submission) ? $submission->getWebform()->id() : NULL;
  }
}