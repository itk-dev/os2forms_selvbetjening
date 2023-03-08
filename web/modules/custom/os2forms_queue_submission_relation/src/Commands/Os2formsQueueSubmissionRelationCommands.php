<?php

namespace Drupal\os2forms_queue_submission_relation\Commands;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\os2forms_queue_submission_relation\Helper\Helper;
use Drush\Commands\DrushCommands;

/**
 * Drush commands related to os2forms_queue_submission_relation module.
 */
class Os2formsQueueSubmissionRelationCommands extends DrushCommands {

  /**
   * The os2forms_queue_submission_relation helper.
   *
   * @var \Drupal\os2forms_queue_submission_relation\Helper\Helper
   */
  protected Helper $helper;

  /**
   * The AdvancedQueueProcessSubscriber constructor.
   *
   * @param \Drupal\os2forms_queue_submission_relation\Helper\Helper $helper
   *   The helper service for os2forms_queue_submission_relation module.
   */
  public function __construct(Helper $helper) {
    $this->helper = $helper;
  }

  /**
   * Import all entries from advanced queue table.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @command os2forms_queue_submission_relation:import
   *
   * @filter-default-field name
   */
  public function import(array $options = ['format' => 'table']) {
    $jobs = $this->helper->getAllQueueJobs();
    foreach ($jobs as $job) {
      $payload = json_decode($job->payload, TRUE);

      try {
        $data = $this->helper->getDataFromPayload($payload);
        $data['job_id'] = (int) $job->job_id;
        $this->helper->addUpdateRelation($data);
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {

      }
    }
  }

}
