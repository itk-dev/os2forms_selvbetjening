<?php

namespace Drupal\os2forms_rest_api\EventSubscriber;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber.
 */
class EventSubscriber implements EventSubscriberInterface {
  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private RouteMatchInterface $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $currentUser;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private EntityStorageInterface $submissionStorage;

  /**
   * Constructor.
   */
  public function __construct(RouteMatchInterface $routeMatch, AccountProxyInterface $currentUser, EntityTypeManagerInterface $entityTypeManager) {
    $this->routeMatch = $routeMatch;
    $this->currentUser = $currentUser;
    $this->submissionStorage = $entityTypeManager->getStorage('webform_submission');
  }

  /**
   * On request handler.
   */
  public function onRequest(KernelEvent $event) {
    if ($this->currentUser->isAnonymous()
      || 'rest.webform_rest_submission.GET' !== $this->routeMatch->getRouteName()) {
      return;
    }

    $webformId = $this->routeMatch->getParameter('webform_id');
    $uuid = $this->routeMatch->getParameter('uuid');
    if (!isset($webformId, $uuid)) {
      return;
    }

    $submissionIds = $this->submissionStorage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('uuid', $uuid)
      ->execute();
    $submission = $this->submissionStorage->load(array_key_first($submissionIds));

    if (NULL === $submission) {
      return;
    }

    assert($submission instanceof WebformSubmissionInterface);
    $webform = $submission->getWebform();
    if (NULL === $webform || $webformId !== $webform->id()) {
      return;
    }

    // @todo Get this from the webform.
    $allowedUsers = $webform->getThirdPartySetting('os2forms_rest_api', 'allowed_users', []);

    if (!empty($allowedUsers) && !in_array($this->currentUser->id(), $allowedUsers, TRUE)) {
      throw new AccessDeniedHttpException('Access denied');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // @see https://www.drupal.org/project/drupal/issues/2924954#comment-12350447
      KernelEvents::REQUEST => ['onRequest', 31],
    ];
  }

}
