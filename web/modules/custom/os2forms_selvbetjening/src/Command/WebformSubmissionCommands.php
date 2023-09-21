<?php

namespace Drupal\os2forms_selvbetjening\Command;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformEntityStorageInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Drupal\webform_revisions\Entity\WebformRevisions;
use Drush\Commands\DrushCommands;
use Symfony\Component\Yaml\Yaml;

/**
 * Webform submission commands.
 */
class WebformSubmissionCommands extends DrushCommands {
  /**
   * The webform storage.
   *
   * @var \Drupal\webform\WebformEntityStorageInterface
   */
  private readonly WebformEntityStorageInterface $webformStorage;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  private readonly WebformSubmissionStorageInterface $webformSubmissionStorage;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    private readonly Connection $database,
    private readonly AccountInterface $currentUser
  ) {
    $this->webformStorage = $entityTypeManager->getStorage('webform');
    $this->webformSubmissionStorage = $entityTypeManager->getStorage('webform_submission');
  }

  /**
   * List webforms revisions.
   *
   * @command os2forms_selvbetjening:webform:list-revisions
   */
  public function listWebformRevisions() {
    /** @var \Drupal\webform\WebformInterface[] $webforms */
    $webforms = $this->webformStorage->loadMultiple();

    $userInfo = sprintf('%s (#%s)', $this->currentUser->getAccountName(), $this->currentUser->id());
    foreach ($webforms as $webform) {
      $this->writeln(
        Yaml::dump([
          'id' => $webform->id(),
          'label' => $webform->label(),
          'revisionId' => $webform instanceof WebformRevisions ? $webform->getRevisionId() : '👻',
          'current user' => $userInfo,
        ])
      );
    }
  }

  /**
   * List stray submissions.
   *
   * List submission where the loaded webform does not match the expected webform.
   *
   * @param string $webformId
   *   The webform id. Use '*' to list check all webforms.
   * @param array $options
   *   The command options.
   *
   * @option bool fix
   *   Fix webform revision on stray submissions.
   *
   * @command os2forms_selvbetjening:webform-submission:list-stray-submissions
   *
   * @phpstan-param array<string, mixed> $options
   */
  public function listStraySubmissions(string $webformId, array $options = [
    'fix' => FALSE,
  ]) {
    $webforms = '*' === $webformId
      ? $this->webformStorage->loadMultiple()
      : $this->webformStorage->loadMultiple([$webformId]);

    foreach ($webforms as $webform) {
      /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
      $submissions = $this->webformSubmissionStorage->loadByProperties([
        'webform_id' => $webform->id(),
      ]);

      foreach ($submissions as $submission) {
        if ($webform->id() !== $submission->getWebform()->id()) {
          $this->writeln(
            Yaml::dump([
              'submission' => [
                'id' => $submission->id(),
                'webform.id' => $submission->getWebform()->id(),
                'data' => $submission->getData(),
              ],
              'webform' => [
                'id' => $webform->id(),
                'revisionId' => $webform instanceof WebformRevisions ? $webform->getRevisionId() : '👻',
              ],
            ], PHP_INT_MAX)
          );

          if ($options['fix']) {
            $this->database->update('webform_submission')
              ->fields([
                'webform_revision' => NULL,
              ])
              ->condition('sid', $submission->id())
              ->execute();

            $this->writeln(sprintf('Fixed webform revision on submission %s', $submission->id()));
          }
        }
      }
    }
  }

}
