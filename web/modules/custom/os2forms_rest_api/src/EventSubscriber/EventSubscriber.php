<?php

namespace Drupal\os2forms_rest_api\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\os2forms_rest_api\WebformHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
   * The webform helper.
   *
   * @var \Drupal\os2forms_rest_api\WebformHelper
   */
  private WebformHelper $webformHelper;

  /**
   * Constructor.
   */
  public function __construct(RouteMatchInterface $routeMatch, AccountProxyInterface $currentUser, WebformHelper $webformHelper) {
    $this->routeMatch = $routeMatch;
    $this->currentUser = $currentUser;
    $this->webformHelper = $webformHelper;
  }

  /**
   * On request handler.
   *
   * Check for user access to webform API resource.
   */
  public function onRequest(KernelEvent $event) {
    $routeName = $this->routeMatch->getRouteName();
    $restRouteNames = [
      'rest.webform_rest_elements.GET',
      'rest.webform_rest_fields.GET',
      'rest.webform_rest_submission.GET',
      'rest.webform_rest_submission.PATCH',
      'rest.webform_rest_submit.POST',
    ];
    if ($this->currentUser->isAnonymous() || !in_array($routeName, $restRouteNames, TRUE)) {
      return;
    }

    $webformId = $this->routeMatch->getParameter('webform_id');
    $submissionUuid = $this->routeMatch->getParameter('uuid');

    // Handle webform submission.
    if ('rest.webform_rest_submit.POST' === $routeName) {
      try {
        $content = json_decode($event->getRequest()->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);
        $webformId = (string) $content['webform_id'];
      }
      catch (\JsonException $exception) {
      }
    }

    if (!isset($webformId)) {
      throw new BadRequestHttpException('Cannot get webform id');
    }

    $webform = $this->webformHelper->getWebform($webformId, $submissionUuid);

    if (NULL === $webform) {
      return;
    }

    $allowedUsers = $this->webformHelper->getAllowedUsers($webform);
    if (!empty($allowedUsers) && !isset($allowedUsers[$this->currentUser->id()])) {
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
