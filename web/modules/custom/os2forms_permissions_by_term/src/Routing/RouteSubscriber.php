<?php

namespace Drupal\os2forms_permissions_by_term\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('entity.webform_submission.collection');
    if ($route) {
      $route->setRequirement('_access', 'FALSE');
    }
  }
}
