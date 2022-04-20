<?php

namespace Drupal\os2forms_rest_api;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Webform helper for helping with webforms.
 */
class WebformHelper {
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Implements hook_webform_third_party_settings_form_alter().
   */
  public function webformThirdPartySettingsFormAlter(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityForm $formObject */
    $formObject = $form_state->getFormObject();
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $formObject->getEntity();
    $settings = $webform->getThirdPartySetting('os2forms', 'os2forms_rest_api');

    $form['third_party_settings']['os2forms']['os2forms_rest_api'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => 'REST API',
      '#tree' => TRUE,
    ];

    $allowedUsers = $this->loadUsers($settings['allowed_users'] ?? []);

    $form['third_party_settings']['os2forms']['os2forms_rest_api']['allowed_users'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#tags' => TRUE,
      '#title' => $this->t('Allowed users'),
      '#description' => $this->t("Limits users allowed to access this form's data via the REST API"),
      '#default_value' => $allowedUsers,
    ];
  }

  /**
   * Get webform by id or submission uuid.
   *
   * If submission uuid is specified (i.e. not null), the submission's webform's
   * id must match the specified webform id.
   *
   * @return \Drupal\webform\WebformInterface|null
   *   The webform if found.
   */
  public function getWebform(string $webformId, string $submissionUuid = NULL): ?WebformInterface {
    if (NULL !== $submissionUuid) {
      $storage = $this->entityTypeManager->getStorage('webform_submission');
      $submissionIds = $storage
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('uuid', $submissionUuid)
        ->execute();
      $submission = $storage->load(array_key_first($submissionIds));

      if (NULL === $submission) {
        return NULL;
      }

      assert($submission instanceof WebformSubmissionInterface);
      $webform = $submission->getWebform();
      if ($webformId !== $webform->id()) {
        return NULL;
      }

      return $webform;
    }

    return $this->entityTypeManager
      ->getStorage('webform')
      ->load($webformId);
  }

  /**
   * Get users allowed to access a webform's data.
   *
   * @return \Drupal\user\UserInterface[]|array
   *   The users.
   */
  public function getAllowedUsers(WebformInterface $webform): array {
    $settings = $webform->getThirdPartySetting('os2forms', 'os2forms_rest_api');
    $allowedUserIds = $settings['allowed_users'] ?? [];

    return $this->loadUsers($allowedUserIds);
  }

  /**
   * Load users.
   */
  private function loadUsers(array $spec): array {
    return $this->entityTypeManager
      ->getStorage('user')
      ->loadMultiple(array_column($spec, 'target_id'));
  }

}
