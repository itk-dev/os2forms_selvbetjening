<?php

namespace Drupal\os2forms_selvbetjening\Plugin\views\field;

use Drupal\author_bulk_assignment\Plugin\views\field\AuthorAssignmentEntityBulkForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;


  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Permissions by term access storage.
   *
   * @var \Drupal\permissions_by_term\Service\AccessStorage
   */
  protected AccessStorage $accessStorage;

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
    AccessStorage $access_storage,
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    MessengerInterface $messenger,
    EntityRepositoryInterface $entity_repository,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $language_manager, $messenger, $entity_repository);
    $this->accessStorage = $access_storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->langcode = $language_manager->getCurrentLanguage()->getId();
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
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
    $current_user = User::load($this->currentUser->id());

    return $this->accessStorage->getPermittedTids($current_user->id(), $current_user->getRoles());
  }

  /**
   * Retrieves a list of users based on term IDs.
   *
   * @param array $userTermsIds
   *   An array of term IDs for which to retrieve the associated users.
   *
   * @return array
   *   An associative array where the keys are user IDs and the values are
   *   the display names of the users. Returns empty array if no users are found.
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
   * {@inheritdoc}
   */
  public function viewsForm(&$form, FormStateInterface $form_state): void {
    parent::viewsForm($form, $form_state);

    $form['header']['node_bulk_form']['assignee_uid']['#type'] = 'select';
    $form['header']['node_bulk_form']['assignee_uid']['#chosen'] = TRUE;

    $userTermsIds = $this->getUserTerms();
    $users = $this->getUsersByTermId($userTermsIds);
    $form['header']['node_bulk_form']['assignee_uid']['#options'] = $users;
  }

}
