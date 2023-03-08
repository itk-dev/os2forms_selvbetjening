<?php

namespace Drupal\os2forms_queue_submission_relation\Commands;

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
   * @command os2forms_queue_submission_relation:import
   */
  public function import() {
    $this->helper->handleImport();
  }

}
