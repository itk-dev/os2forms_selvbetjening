<?php

namespace Drupal\os2forms_selvbetjening\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber used to disable all login routes.
 *
 * @see https://drupal.stackexchange.com/a/272234
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Deny access to unwanted routes.
    $disallowedRouteNames = [
      'user.register',
      'user.pass',
    ];
    foreach ($disallowedRouteNames as $name) {
      if ($route = $collection->get($name)) {
        $route->setRequirement('_access', 'FALSE');
      }
    }
  }

}
