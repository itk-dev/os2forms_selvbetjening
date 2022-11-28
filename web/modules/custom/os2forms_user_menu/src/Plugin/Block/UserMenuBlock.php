<?php

namespace Drupal\os2forms_user_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\os2forms_cvr_lookup\Service\CvrServiceInterface;
use Drupal\os2forms_cpr_lookup\Service\CprServiceInterface;
use Drupal\os2web_nemlogin\Service\AuthProviderService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an user menu block.
 *
 * @Block(
 *   id = "os2forms_user_menu_user_menu",
 *   admin_label = @Translation("User menu"),
 *   category = @Translation("OS2Forms")
 * )
 */
class UserMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The OS2Web Nemlogin authorization provider.
   *
   * @var \Drupal\os2web_nemlogin\Service\AuthProviderService
   */
  protected AuthProviderService $authProvider;

  /**
   * The OS2forms CVR service.
   *
   * @var \Drupal\os2forms_cvr_lookup\Service\CvrServiceInterface
   */
  protected CvrServiceInterface $cvrService;

  /**
   * The OS2forms CPR service.
   *
   * @var \Drupal\os2forms_cpr_lookup\Service\CprServiceInterface
   */
  protected CprServiceInterface $cprService;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\os2web_nemlogin\Service\AuthProviderService $authProvider
   * @param \Drupal\os2forms_cvr_lookup\Service\CvrServiceInterface $cvrService
   * @param \Drupal\os2forms_cpr_lookup\Service\CprServiceInterface $cprService
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AuthProviderService $authProvider, CvrServiceInterface $cvrService, CprServiceInterface $cprService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->authProvider = $authProvider;
    $this->cvrService = $cvrService;
    $this->cprService = $cprService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('os2web_nemlogin.auth_provider'),
      $container->get('os2forms_cvr_lookup.service'),
      $container->get('os2forms_cpr_lookup.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $webform = NULL;
    // If there is no auth plugin show nothing.
    if (empty($this->authProvider)) {
      return [];
    }
    $plugin = $this->authProvider->getActivePlugin();

    if (empty($plugin)) {
      return [];
    }

    // Determine if we have an entity to work with.
    $pageEntity = $this->get_page_entity();

    // Get webform from node if a reference exists.
    if ('node' === $pageEntity->getEntityTypeId()) {
      if ('webform' === $pageEntity->bundle()) {
        $webformList = $pageEntity->get('webform')->referencedEntities();
        if ($webformList) {
          $webform = $webformList['0'];
        }
      }
    }

    if ('webform' === $pageEntity->getEntityTypeId()) {
      $webform = $pageEntity;
    }

    // If there is no webform found show nothing.
    if (!$webform) {
      return [];
    }

    $webformNemIdSettings = $webform->getThirdPartySetting('os2forms', 'os2forms_nemid');

    // If nemlogin is not enabled show nothing.
    if (FALSE === $webformNemIdSettings['nemlogin_auto_redirect']) {
      return [];
    }

    $name = NULL;

    if (!$plugin->isAuthenticated()) {
      $url = $this->authProvider->getLoginUrl();
    }
    else {
      $url = $this->authProvider->getLogoutUrl();
      // Use cvr name if one exists.
      if ($cvr = $plugin->fetchValue('cvr')) {
        try {
          $cvrResponse = $this->cvrService->search($cvr);
          $name = $cvrResponse->getName();
        }
        catch (\Exception $e) {
          // If we could not get cvr information show nothing.
          return [];
        }
      }
      else {
        try {
          $cprResponse = $this->cprService->search($plugin->fetchValue('cpr'));
          $name = $cprResponse->getName();
        }
        catch (\Exception $e) {
          // If we could not get cpr information show nothing.
          return [];
        }
      }
    }

    // Show user information.
    $build['content'] = [
      '#theme' => 'user_menu',
      '#name' => $name,
      '#url' => $url,
      '#attached' => [
        'library' => ['os2forms_user_menu/user_menu'],
      ],
    ];

    return $build;
  }

  /**
   * Determine if an entity exists on the page.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface|mixed|null
   */
  private function get_page_entity() {
    $page_entity = &drupal_static(__FUNCTION__, NULL);
    if (isset($page_entity)) {
      return $page_entity ?: NULL;
    }
    $current_route = \Drupal::routeMatch();
    foreach ($current_route->getParameters() as $param) {
      if ($param instanceof EntityInterface) {
        $page_entity = $param;
        break;
      }
    }
    if (!isset($page_entity)) {
      // Some routes don't properly define entity parameters.
      // Thus, try to load them by its raw Id, if given.
      $entity_type_manager = \Drupal::entityTypeManager();
      $types = $entity_type_manager->getDefinitions();
      foreach ($current_route->getParameters()->keys() as $param_key) {
        if (!isset($types[$param_key])) {
          continue;
        }
        if ($param = $current_route->getParameter($param_key)) {
          if (is_string($param) || is_numeric($param)) {
            try {
              $page_entity = $entity_type_manager->getStorage($param_key)->load($param);
            }
            catch (\Exception $e) {
            }
          }
          break;
        }
      }
    }
    if (!isset($page_entity) || !$page_entity->access('view')) {
      $page_entity = FALSE;
      return NULL;
    }
    return $page_entity;
  }

}
