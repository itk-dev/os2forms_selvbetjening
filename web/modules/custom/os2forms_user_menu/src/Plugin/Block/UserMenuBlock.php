<?php

namespace Drupal\os2forms_user_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_digital_post\Exception\RuntimeException;
use Drupal\os2web_datalookup\LookupResult\CompanyLookupResult;
use Drupal\os2web_datalookup\LookupResult\CprLookupResult;
use Drupal\os2web_datalookup\Plugin\DataLookupManager;
use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupInterfaceCompany;
use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupInterfaceCpr;
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
final class UserMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * The OS2Web Nemlogin authorization provider.
   *
   * @var \Drupal\os2web_nemlogin\Service\AuthProviderService
   */
  protected AuthProviderService $authProvider;

  /**
   * The OS2forms CVR service.
   *
   * @var \Drupal\os2web_datalookup\Plugin\DataLookupManager
   */
  protected DataLookupManager $dataLookupManager;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Block constructor.
   *
   * @param array $configuration
   *   Block configuration.
   * @param string $plugin_id
   *   Block plugin id.
   * @param mixed $plugin_definition
   *   Block plugin definition.
   * @param \Drupal\os2web_nemlogin\Service\AuthProviderService $authProvider
   *   The OS2Web Nemlogin authorization provider.
   * @param \Drupal\os2web_datalookup\Plugin\DataLookupManager $dataLookupManager
   *   The OS2forms Data lookup service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AuthProviderService $authProvider, DataLookupManager $dataLookupManager, RouteMatchInterface $routeMatch, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->authProvider = $authProvider;
    $this->dataLookupManager = $dataLookupManager;
    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
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
      $container->get('plugin.manager.os2web_datalookup'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Disable block caching.
    return 0;
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

    // Determine if we have an entity to work with.
    $pageEntity = $this->getPageEntity();

    // Get webform from node if a reference exists.
    if ('node' === $pageEntity->getEntityTypeId()) {
      if ('webform' === $pageEntity->bundle()) {
        $webformList = $pageEntity->get('webform')->referencedEntities();
        $webform = reset($webformList) ?: NULL;
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

    $nemloginAutoRedirect = (bool) ($webformNemIdSettings['nemlogin_auto_redirect'] ?? FALSE);

    if (!$nemloginAutoRedirect) {
      return [];
    }

    $name = NULL;
    $loginUrl = NULL;
    $logoutUrl = NULL;

    if (!$plugin->isAuthenticated()) {
      $loginUrl = $this->authProvider->getLoginUrl();
    }
    else {
      $logoutUrl = $this->authProvider->getLogoutUrl();
      // Use cvr name if one exists.
      if ($cvr = $plugin->fetchValue('cvr')) {
        try {
          $cvrResponse = $this->lookupCvr($cvr);
          $name = $cvrResponse->getName();
        }
        catch (\Exception $e) {
          // If we could not get cvr information show nothing.
          $name = $this->t('Logged in');
        }
      }
      elseif ($cpr = $plugin->fetchValue('cpr')) {
        try {
          $cprResponse = $this->lookupCpr($cpr);
          $name = $cprResponse->getName();
        }
        catch (\Exception $e) {
          // If we could not get cpr information show nothing.
          $name = $this->t('Logged in');
        }
      }
    }

    // Show user information.
    $build['content'] = [
      '#theme' => 'os2forms_user_menu',
      '#name' => $name,
      '#login_url' => $loginUrl,
      '#logout_url' => $logoutUrl,
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
   *   An entity found on the page.
   */
  private function getPageEntity() {
    $page_entity = &drupal_static(__FUNCTION__, NULL);
    if (isset($page_entity)) {
      return $page_entity ?: NULL;
    }

    foreach ($this->routeMatch->getParameters() as $param) {
      if ($param instanceof EntityInterface) {
        $page_entity = $param;
        break;
      }
    }
    if (!isset($page_entity)) {
      // Some routes don't properly define entity parameters.
      // Thus, try to load them by its raw Id, if given.
      $types = $this->entityTypeManager->getDefinitions();
      foreach ($this->routeMatch->getParameters()->keys() as $param_key) {
        if (!isset($types[$param_key])) {
          continue;
        }
        if ($param = $this->routeMatch->getParameter($param_key)) {
          if (is_string($param) || is_numeric($param)) {
            try {
              $page_entity = $this->entityTypeManager->getStorage($param_key)->load($param);
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

  /**
   * Look up CPR.
   */
  public function lookupCpr(string $cpr): CprLookupResult {
    $instance = $this->dataLookupManager->createDefaultInstanceByGroup('cpr_lookup');
    if (!($instance instanceof DataLookupInterfaceCpr)) {
      throw new RuntimeException('Cannot get CPR data lookup instance');
    }
    $lookupResult = $instance->lookup($cpr);
    if (!$lookupResult->isSuccessful()) {
      throw new RuntimeException('Cannot lookup CPR');
    }

    return $lookupResult;
  }

  /**
   * Look up CVR.
   */
  public function lookupCvr(string $cvr): CompanyLookupResult {
    $instance = $this->dataLookupManager->createDefaultInstanceByGroup('cvr_lookup');
    if (!($instance instanceof DataLookupInterfaceCompany)) {
      throw new RuntimeException('Cannot get CVR data lookup instance');
    }
    $lookupResult = $instance->lookup($cvr);
    if (!$lookupResult->isSuccessful()) {
      throw new RuntimeException('Cannot lookup CVR');
    }

    return $lookupResult;
  }

}
