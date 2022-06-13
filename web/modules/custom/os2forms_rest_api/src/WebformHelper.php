<?php

namespace Drupal\os2forms_rest_api;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\key_auth\Authentication\Provider\KeyAuth;
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
   * The current user manager.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $currentUser;

  /**
   * The key authentication service.
   *
   * @var \Drupal\key_auth\Authentication\Provider\KeyAuth
   */
  private KeyAuth $keyAuth;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser, KeyAuth $keyAuth) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->keyAuth = $keyAuth;
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

    $form['third_party_settings']['os2forms']['os2forms_rest_api']['api_info']['endpoints'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API endpoints'),

      'links' => [],

      'messages' => [
        '#markup' => $this->t('Share these endpoints with people that must will use the REST API. Authentification is required to access the endpoints.'),
      ],

    ];

    $routes = [
      'rest.webform_rest_elements.GET',
      'rest.webform_rest_fields.GET',
      'rest.webform_rest_submission.GET',
    ];
    $requireUuid = static function ($route) {
      return in_array(
        $route,
        [
          'rest.webform_rest_submission.GET',
          'rest.webform_rest_submission.PATCH',
        ],
        TRUE
      );
    };

    $form['third_party_settings']['os2forms']['os2forms_rest_api']['api_info']['endpoints']['links']['#prefix'] = '<ol>';
    $form['third_party_settings']['os2forms']['os2forms_rest_api']['api_info']['endpoints']['links']['#suffix'] = '</ol>';

    foreach ($routes as $route) {
      $parameters = [];

      if ('rest.webform_rest_submit.POST' !== $route) {
        $parameters['webform_id'] = $webform->id();
      }
      $uuidPlaceholder = '{uuid}';
      if ($requireUuid($route)) {
        $parameters['uuid'] = $uuidPlaceholder;
      }

      $url = Url::fromRoute($route, $parameters, ['absolute' => TRUE]);
      $form['third_party_settings']['os2forms']['os2forms_rest_api']['api_info']['endpoints']['links'][$route] = [
        '#type' => 'link',
        '#title' => str_replace(urlencode($uuidPlaceholder), $uuidPlaceholder, $url->toString()),
        '#url' => $url,
        '#prefix' => '<li>',
        '#suffix' => '</li>',
      ];
    }

    if ($this->currentUser->isAuthenticated()) {
      /** @var \Drupal\user\Entity\User $apiUser */
      $apiUser = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      // Don't show API data links if current user is not included in
      // (non-empty) list of allowed users.
      if (!empty($allowedUsers) && !isset($allowedUsers[$apiUser->id()])) {
        $apiUser = NULL;
      }
      $apiKey = $apiUser ? $apiUser->api_key->value : NULL;
      if (!empty($apiKey)) {
        $form['third_party_settings']['os2forms']['os2forms_rest_api']['api_info']['endpoints_test'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Test API endpoints'),

          'links' => [],

          'message' => [
            '#markup' => $this->t('These are only for checking the API responses for user %user. <strong>Do not</strong> share these urls!', ['%user' => $apiUser->getAccountName()]),
          ],
        ];

        $form['third_party_settings']['os2forms']['os2forms_rest_api']['api_info']['endpoints_test']['links']['#prefix'] = '<ol>';
        $form['third_party_settings']['os2forms']['os2forms_rest_api']['api_info']['endpoints_test']['links']['#suffix'] = '</ol>';

        foreach ($routes as $route) {
          $parameters = [];

          if ('rest.webform_rest_submit.POST' !== $route) {
            $parameters['webform_id'] = $webform->id();
          }
          $uuidPlaceholder = '{uuid}';
          if ($requireUuid($route)) {
            $parameters['uuid'] = $uuidPlaceholder;
          }
          $parameters['api-key'] = $apiKey;

          $url = Url::fromRoute($route, $parameters, ['absolute' => TRUE]);
          $form['third_party_settings']['os2forms']['os2forms_rest_api']['api_info']['endpoints_test']['links'][$route] = [
            '#type' => 'link',
            '#title' => str_replace(urlencode($uuidPlaceholder), $uuidPlaceholder, $url->toString()),
            '#url' => $url,
            '#prefix' => '<li>',
            '#suffix' => '</li>',
          ];
        }
      }
    }
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

  /**
   * Implements hook_file_download().
   */
  public function fileDownload(string $uri) {
    $request = \Drupal::request();

    if ($user = $this->keyAuth->authenticate($request)) {
      // Find webform id from uri, see example uri.
      // @Example: private://webform/some_webform_id/119/some_file_name.png
      $pattern = '/private:\/\/webform\/(?<webform>[^\/]*)/';
      if (!preg_match($pattern, $uri, $matches)) {
        // Something is not right, deny access.
        return -1;
      }

      // User has API access.
      $webform = \Drupal::entityTypeManager()->getStorage('webform')->load($matches['webform']);
      $settings = $webform->getThirdPartySetting('os2forms', 'os2forms_rest_api');

      $allowedUsers = $this->loadUsers($settings['allowed_users'] ?? []);

      // If allowed users is non-empty and user is not in there deny access.
      if (!empty($allowedUsers) && !isset($allowedUsers[$user->id()])) {
        return -1;
      }
    }
    return NULL;
  }

}
