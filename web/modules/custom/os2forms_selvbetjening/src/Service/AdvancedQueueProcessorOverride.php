<?php

namespace Drupal\os2forms_selvbetjening\Service;

use Drupal\advancedqueue\Entity\QueueInterface;
use Drupal\advancedqueue\Event\AdvancedQueueEvents;
use Drupal\advancedqueue\Event\JobEvent;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Processor;
use Drupal\Core\Utility\Error;

/**
 * Custom processor for Advanced Queue with modified retry logic.
 * Looks for a retry_multiplier in the $jobTypePluginDefinition.
 */
class AdvancedQueueProcessorOverride extends Processor {

  /**
   * {@inheritdoc}
   */
  public function processJob(Job $job, QueueInterface $queue): JobResult {
    $this->eventDispatcher->dispatch(new JobEvent($job), AdvancedQueueEvents::PRE_PROCESS);

    try {
      $job_type = $this->jobTypeManager->createInstance($job->getType());
      $result = $job_type->process($job);
    }
    catch (\Throwable $e) {
      $job_type = NULL;
      $result = JobResult::failure($e->getMessage());

      $variables = Error::decodeException($e);
      $this->logger->error('%type: @message in %function (line %line of %file).', $variables);
    }

    // Update the job with the result.
    $job->setState($result->getState());
    $job->setMessage($result->getMessage());
    $this->eventDispatcher->dispatch(new JobEvent($job), AdvancedQueueEvents::POST_PROCESS);
    // Pass the job back to the backend.
    $queue_backend = $queue->getBackend();
    if ($job->getState() == Job::STATE_SUCCESS) {
      $queue_backend->onSuccess($job);
    }
    elseif ($job->getState() == Job::STATE_FAILURE && !$job_type) {
      // The job failed because of an exception, no need to retry.
      $queue_backend->onFailure($job);
    }
    elseif ($job->getState() == Job::STATE_FAILURE && $job_type) {
      $jobTypePluginDefinition = $job_type->getPluginDefinition();
      $retryMultiplier = $jobTypePluginDefinition['retry_multiplier'] ?? 1;
      $max_retries = !is_null($result->getMaxRetries()) ? $result->getMaxRetries() : $job_type->getMaxRetries();
      $retry_delay = !is_null($result->getRetryDelay()) ? $result->getRetryDelay() : $job_type->getRetryDelay();
      if ($job->getNumRetries() < $max_retries) {
        $retry_delay = $job->getNumRetries()*$retryMultiplier*$retry_delay;
        $queue_backend->retryJob($job, $retry_delay);
      }
      else {
        $queue_backend->onFailure($job);
      }
    }

    return $result;
  }
}