<?php

namespace Drupal\os2forms_selvbetjening\Plugin\views\field;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\author_bulk_assignment\Plugin\views\field\AuthorAssignmentEntityBulkForm;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\permissions_by_term\Service\AccessStorage;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a user operations bulk form element with term-based restrictions.
 *
 * @ViewsField("author_assignment_node_bulk_form_override")
 */
class AuthorAssignmentNodeBulkFormOverride extends AuthorAssignmentEntityBulkForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current language code.
   *
   * @var string
   */
  protected string $langcode;

  /**
   * Constructs a new AuthorAssignmentNodeBulkFormOverride instance.
   */
  public function __construct(
    private readonly AccessStorage $accessStorage,
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $language_manager,
    MessengerInterface $messenger,
    EntityRepositoryInterface $entity_repository,
    private readonly AccountProxyInterface $currentUser,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entityTypeManager, $language_manager, $messenger, $entity_repository);
    $this->langcode = $language_manager->getCurrentLanguage()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    /** @var static */
    return new self(
      $container->get('permissions_by_term.access_storage'),
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('messenger'),
      $container->get('entity.repository'),
      $container->get('current_user')
    );
  }

  /**
   * Gets the taxonomy terms that the current user has access to.
   *
   * @return array
   *   An array of term IDs that the current user has permission to access.
   *   Returns an empty array if no terms are accessible or if the user
   *   has no permissions.
   */
  private function getUserTerms(): array {
    $currentUser = User::load($this->currentUser->id());

    return $this->accessStorage->getPermittedTids($currentUser->id(), $currentUser->getRoles());
  }

  /**
   * Retrieves a list of users based on term IDs.
   *
   * @param array $userTermsIds
   *   An array of term IDs for which to retrieve the associated users.
   *
   * @return array
   *   An associative array where the keys are user IDs and the values are
   *   the display names of the users. Returns an empty array if no users
   *   are found.
   */
  private function getUsersByTermId(array $userTermsIds): array {
    $users = [];
    if (!empty($userTermsIds)) {
      // Get all users that have access to these terms.
      foreach ($userTermsIds as $termId) {
        $userIdsResult = $this->accessStorage->getAllowedUserIds($termId, $this->langcode);
        foreach ($userIdsResult as $userId) {
          if (!isset($users[$userId])) {
            if ($user = User::load($userId)) {
              $users[$userId] = $user->getDisplayName();
            }
          }
        }
      }
    }
    return $users;
  }

  /**
   * Filters users by their access to webform terms.
   *
   *
   * @param array $users
   *   An associative array of users where the key is the user ID and
   *   the value is the username.
   * @param array $terms
   *   An array of terms or term groups defining access requirements.
   *
   * @return array
   *   A filtered list of users who have access to the given terms.
   */
  private function filterUsersByWebformAccess(array $users, array $terms): array {
    $mergedTerms = array_values($terms);
    $mergedTerms = !empty($mergedTerms) ? (is_array($mergedTerms[0]) ? array_merge(...$mergedTerms) : $mergedTerms) : [];

    return array_filter($users, function($userName, $userId) use ($mergedTerms) {
      $user = User::load($userId);
      $userTermsIds = $this->accessStorage->getPermittedTids($user->id(), $user->getRoles());

      // Check if all terms from $mergedTerms exist in $userTermsIds
      return empty($mergedTerms) || count(array_intersect($userTermsIds, $mergedTerms)) === count($mergedTerms);
    }, ARRAY_FILTER_USE_BOTH);
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(&$form, FormStateInterface $form_state): void {
    parent::viewsForm($form, $form_state);

    $form['header']['node_bulk_form']['assignee_uid']['#type'] = 'select';
    $form['header']['node_bulk_form']['assignee_uid']['#chosen'] = TRUE;
    $form['header']['node_bulk_form']['action']['#options']['node_author_bulk_assignment_action'] = $this->t('Change ownership');

    $userTermsIds = $this->getUserTerms();
    $users = $this->getUsersByTermId($userTermsIds);
    $form_state->set('users', $users);

    $form['header']['node_bulk_form']['assignee_uid']['#options'] = $users;
  }

  /**
   * {@inheritDoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state): void {
    parent::viewsFormValidate($form, $form_state);

    $users = $form_state->get('users');
    $user_input = $form_state->getUserInput();
    $selected = array_filter($user_input[$this->options['id']]);
    $webformPermissionsByTermArray = [];

    foreach ($selected as $bulk_form_key) {
      $entity = $this->loadEntityFromBulkFormKey($bulk_form_key);
      $webform_field = $entity->get('webform');
      $webform = $webform_field->entity;

      if ($webform) {
        $webformPermissionsByTerm = $webform->getThirdPartySetting('os2forms_permissions_by_term', 'settings');
        // Flatten, disregard duplicates, add results to the main array.
        $webformPermissionsByTermArray = array_values(array_unique(array_merge(
          $webformPermissionsByTermArray,
          array_filter($webformPermissionsByTerm, fn($v, $k) => $v == $k, ARRAY_FILTER_USE_BOTH)
        )));
      }
      else {
        $form_state->setErrorByName('AuthorAssignmentNodeBulkFormError', $this->t('One or more of the selected nodes does not have a webform connected to it.'));
      }
    }

    $filteredUsers = $this->filterUsersByWebformAccess($users, $webformPermissionsByTermArray);
    $selectedAssignee = $form_state->getValue('assignee_uid');

    if (!isset($filteredUsers[$selectedAssignee]) && $selectedAssignee != 0) {
      $form_state->setErrorByName('AuthorAssignmentNodeBulkFormError', $this->t('The selected user does not have access to one or more of the selected webforms.'));
    }
}
}
