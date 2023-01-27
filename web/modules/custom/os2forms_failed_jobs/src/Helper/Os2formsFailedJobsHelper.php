<?php

namespace Drupal\os2forms_failed_jobs\Helper;

use Drupal\Core\Database\Connection;

/**
 * Helper for managing failed jobs..
 */
class Os2formsFailedJobsHelper {

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
   * @return array|bool
   *   A list of attributes related to a job.
   */
  public function getJobFromId(int $jobId) {
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
    return $query->execute()->fetchAssoc();
  }

  /**
   * Get submission id from job.
   *
   * @param int $jobId
   *   The job id.
   *
   * @return int|mixed
   *   The id of a form submission from a job.
   */
  public function getSubmissionIdFromJob(int $jobId) {
    $job = $this->getJobFromId($jobId);
    $payload = json_decode($job['payload'], TRUE);
    $submissionId = 0;

    if (array_key_exists('submissionId', $payload)) {
      $submissionId = $payload['submissionId'];
    }
    if (array_key_exists('submission', $payload)) {
      $submissionId = $payload['submission']['id'];
    }

    return $submissionId;
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
