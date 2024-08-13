<?php

namespace Drupal\os2forms_selvbetjening\Helper;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\os2forms_selvbetjening\Exception\SelvbetjeningException;

/**
 * Helper for install.
 */
class InstallHelper {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleInstaller
   *   Module handler.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(
    private readonly ModuleHandlerInterface $moduleInstaller,
    private readonly Connection $connection
  ) {
  }

  /**
   * Nullify webform revision on existing webform submissions.
   *
   * @throws \Drupal\os2forms_selvbetjening\Exception\SelvbetjeningException
   */
  public function nullifyExistingWebformSubmissionRevisions() {
    if (!$this->moduleInstaller->moduleExists('webform_revisions')) {
      return;
    }

    // Loop through all submissions setting webform_revision to null.
    try {
      $this->connection->update('webform_submission')
        ->fields([
          'webform_revision' => NULL,
        ])
        ->execute();
    }
    catch (\Exception $e) {
      throw new SelvbetjeningException('Unable to update webform revisions on submissions.', $e->getCode(), $e);
    }
  }

}
