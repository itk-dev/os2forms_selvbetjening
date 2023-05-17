<?php

namespace Drupal\os2forms_maestro_webform;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\os2forms_maestro_webform\Form\SettingsForm;
use Drupal\os2forms_maestro_webform\Plugin\WebformHandler\NotificationHandler;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Drupal\webform\WebformTokenManagerInterface;

/**
 * Maestro helper.
 */
class MaestroHelper {
  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  readonly private ImmutableConfig $config;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface|\Drupal\Core\Entity\EntityStorageInterface
   */
  readonly private WebformSubmissionStorageInterface $webformSubmissionStorage;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
    readonly private WebformTokenManagerInterface $tokenManager
  ) {
    $this->config = $configFactory->get(SettingsForm::SETTINGS);
    $this->webformSubmissionStorage = $entityTypeManager->getStorage('webform_submission');
  }

  /**
   * Implements hook_maestro_zero_user_notification().
   */
  public function maestroZeroUserNotification($templateMachineName, $taskMachineName, $queueID, $notificationType) {
    // This only fires with a ZERO user-count on notifications. Use this as you
    // see fit.
    if ('assignment' === $notificationType) {
      $templateTask = MaestroEngine::getTemplateTaskByID($templateMachineName, $taskMachineName);
      $taskType = $templateTask['tasktype'] ?? NULL;
      if (in_array($taskType, ['MaestroWebform', 'MaestroWebformInherit'], TRUE)) {
        if ($processID = MaestroEngine::getProcessIdFromQueueId($queueID) ?: NULL) {
          $entityIdentifiers = MaestroEngine::getAllEntityIdentifiersForProcess($processID);
          foreach ($entityIdentifiers as $entityIdentifier) {
            if ('webform_submission' === ($entityIdentifier['entity_type'] ?? NULL)) {
              $submission = $this->webformSubmissionStorage->load($entityIdentifier['entity_id']);
              if ($submission) {
                $this->handleSubmissionNotification($submission, $templateTask, $queueID);
              }
            }
          }
        }
      }
    }
  }

  /**
   * Implements hook_maestro_can_user_execute_task_alter().
   */
  public function maestroCanUserExecuteTaskAlter(bool &$returnValue, int $queueID, int $userID): void {

    // Check if this is an anonymous user and we've been barred access already.
    if ($userID == 0 && $returnValue === FALSE) {
      // Load the template task and we'll determine if this has our "special"
      // assignment to a known "anonymous" role.
      $templateTask = MaestroEngine::getTemplateTaskByQueueID($queueID);
      $assignments = explode(',', $templateTask['assigned']);

      $knownAnonymousAssignments = array_map(
        static fn (string $role) => 'role:fixed:' . $role,
        array_filter($this->config->get('known_anonymous_roles') ?: [])
      );

      // DEV NOTE!!!! We do NOTHING to ensure that this is a specific task type
      // or even that this is in our desired workflows. This routine will run
      // for each and every task execution test and only if we're anonymous.
      // This should be streamlined and tightened up to check for more specific
      // task types or process types... perhaps...
      // In our very specific use case, we are assigning to a fixed role of
      // Citizen.
      // This could be a task config option to denote that regardless of what's
      // in the assignment, we validate this task as executable one way or
      // another.
      // @todo Add in your own validation routines
      foreach ($assignments as $assignment) {
        if (in_array($assignment, $knownAnonymousAssignments, TRUE)) {
          // This is our use case. Very rigid for now for prototyping/demo
          // purposes.
          $returnValue = TRUE;
        }
      }
    }
  }

  /**
   * Handle submission notification.
   */
  private function handleSubmissionNotification(WebformSubmissionInterface $submission, array $templateTask, int $queueID): void {
    $data = $submission->getData();
    $webform = $submission->getWebform();
    $handlers = $webform->getHandlers('os2forms_maestro_webform_notification');
    foreach ($handlers as $handler) {
      if ($handler->isDisabled() || $handler->isExcluded()) {
        continue;
      }
      $settings = $handler->getSettings();
      $notificationSetting = $settings[NotificationHandler::NOTIFICATION];
      $recipientElement = $notificationSetting[NotificationHandler::RECIPIENT_ELEMENT] ?? NULL;
      $recipient = $data[$recipientElement] ?? NULL;
      if (NULL !== $recipient) {
        // Lifted from MaestroEngine.
        $maestroTokenData = [
          'maestro' => [
            'task' => $templateTask,
            'queueID' => $queueID,
          ],
        ];

        $subject = $this->tokenManager->replace(
          $notificationSetting[NotificationHandler::NOTIFICATION_SUBJECT],
          $submission,
          $maestroTokenData
        );
        $content = $this->tokenManager->replace(
          $notificationSetting[NotificationHandler::NOTIFICATION_CONTENT],
          $submission,
          $maestroTokenData
        );

        if (isset($templateTask['data']['webform_nodes_attached_to'])) {
          $search = '[maestro:task:data:webform_nodes_attached_to]';
          $replace = $templateTask['data']['webform_nodes_attached_to'];
          $subject = str_replace($search, $replace, $subject);
          $content = str_replace($search, $replace, $content);
        }

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
          $this->sendNotificationEmail($recipient, $subject, $content);
        }
        else {
          $this->sendNotificationDigitalPost($recipient, $subject, $content);
        }
      }
    }
  }

  /**
   * Send notification email.
   */
  private function sendNotificationEmail(string $recipient, string $subject, string $content): void {
    mail($recipient, $subject, $content);
  }

  /**
   * Send notification digital post.
   */
  private function sendNotificationDigitalPost(string $recipient, string $subject, string $content): void {
    $this->sendNotificationEmail(
      $recipient . '@digital-post.example.com',
      '(this should have been a digital post)' . $subject,
      $content);
  }

}
