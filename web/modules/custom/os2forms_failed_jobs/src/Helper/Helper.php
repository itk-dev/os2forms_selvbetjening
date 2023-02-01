<?php

namespace Drupal\os2forms_failed_jobs\Helper;

use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Database\Connection;

/**
 * Helper for managing failed jobs..
 */
class Helper {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * Helper constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Get job from job id.
   *
   * @param int $jobId
   *   The job id.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   A list of attributes related to a job.
   */
  public function getJobFromId(int $jobId): ?StatementInterface {
    $query = $this->connection->select('advancedqueue', 'a');
    $query->fields('a', [
      'payload',
      'job_id',
      'queue_id',
      'type',
      'state',
      'message',
    ]);
    $query->condition('job_id', $jobId, '=');

    return $query->execute();
  }

  /**
   * Get submission id from job.
   *
   * @param int $jobId
   *   The job id.
   *
   * @return int|null
   *   The id of a form submission from a job.
   */
  public function getSubmissionIdFromJob(int $jobId): ?int {
    $job = $this->getJobFromId($jobId)->fetchAssoc();
    $payload = json_decode($job['payload'], TRUE);

    return $payload['submissionId'] ?? $payload['submission']['id'] ?? NULL;
  }

  /**
   * Get all jobs from advanced queue table.
   *
   * @return array
   *   A list of jobs.
   */
  public function getAllJobs():array {
    $query = $this->connection->select('advancedqueue', 'a');
    $query->fields('a', ['payload', 'job_id']);

    return $query->execute()->fetchAll();
  }

}
