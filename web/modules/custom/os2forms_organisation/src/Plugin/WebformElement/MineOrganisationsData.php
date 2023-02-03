<?php

namespace Drupal\os2forms_organisation\Plugin\WebformElement;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\os2forms_organisation\Exception\InvalidSettingException;
use Drupal\os2forms_organisation\Helper\OrganisationHelper;
use Drupal\os2forms_organisation\Helper\Settings;
use Drupal\os2forms_organisation\Helper\SettingsInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Provides mine organisation data element.
 *
 * @WebformElement(
 *   id = "mine_organisations_data_element",
 *   label = @Translation("Mine organisation data"),
 *   description = @Translation("Provides a form element to collect organisation data."),
 *   category = @Translation("Organisation"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class MineOrganisationsData extends WebformCompositeBase {
  const DATA_DISPLAY_OPTION_CURRENT_USER = 'current_user';
  const DATA_DISPLAY_OPTION_MANAGER = 'manager';

  /**
   * Organisation Settings.
   *
   * @var \Drupal\os2forms_organisation\Helper\SettingsInterface|Settings
   */
  protected SettingsInterface $settings;

  /**
   * Organisation Helper.
   *
   * @var \Drupal\os2forms_organisation\Helper\OrganisationHelper
   */
  protected OrganisationHelper $organisationHelper;

  /**
   * Property accessor.
   *
   * @var \Symfony\Component\PropertyAccess\PropertyAccessor
   */
  protected PropertyAccessor $propertyAccessor;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private RouteMatchInterface $routeMatch;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->settings = $container->get(Settings::class);
    $instance->organisationHelper = $container->get(OrganisationHelper::class);
    $instance->propertyAccessor = $container->get('property_accessor');
    $instance->routeMatch = $container->get('current_route_match');
    $instance->account = $container->get('current_user');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->formatTextItemValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'data_type' => 'user',
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['data_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select which data should be displayed'),
      '#required' => TRUE,
      '#options' => [
        self::DATA_DISPLAY_OPTION_CURRENT_USER => $this->t('Logged in user'),
        self::DATA_DISPLAY_OPTION_MANAGER => $this->t('Manager of user'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $subElements = [
      'name',
      'email',
      'az',
      'phone',
      'location',
      'organisation_enhed',
      'organisation_adresse',
      'organisation_niveau_2',
      'magistrat',
    ];

    $lines = [];

    foreach ($subElements as $subElement) {
      if (!empty($value[$subElement])) {
        $lines[$subElement] = $value[$subElement];
      }
    }

    return $lines;
  }

  /**
   * Alters form.
   */
  public function alterForm(array &$element, array &$form, FormStateInterface $form_state) {

    if (!isset($form['#webform_id'])) {
      return;
    }

    // Only alter when displaying submission form.
    $accessCheckRouteNames = [
      // Webform attached to a node.
      'entity.node.canonical',
      // Creating a new submission.
      'entity.webform.canonical',
      // Editing a submission.
      'entity.webform_submission.edit_form',
    ];

    if (!in_array($this->routeMatch->getRouteName(), $accessCheckRouteNames, TRUE)) {
      return;
    }

    if ('mine_organisations_data_element' === $element['#type']) {
      // Notice that this takes the elements from the form.
      $compositeElement = &NestedArray::getValue($form['elements'], $element['#webform_parents']);

      if (!isset($element['#data_type'])) {
        throw new InvalidSettingException(sprintf('Invalid element configuration. OrganisationData element: %s, should contain a data display option', $form['#webform_id']));
      }

      $options = $this->buildOrganisationFunktionOptions($element['#data_type']);

      if (empty($options)) {
        // A user must have at least one funktion (ansættelse).
        return;
      }

      $this->updateBasicSubElements($compositeElement, $element['#data_type']);

      // If there is only one organisation funktion (ansættelse),
      // preselect it and fill out the elements that require it.
      // @todo Handle multiple ansættelser with js
      if (count($options) === 1) {
        $key = array_key_first($options);
        $compositeElement['#organisations_funktion__value'] = $key;

        $this->updateFunktionSubElements($compositeElement, $key);
      }

      // @see https://www.drupal.org/docs/8/modules/webform/webform-cookbook/how-to-alter-properties-of-a-composites-sub-elements
      $compositeElement['#organisations_funktion__options'] = $options;
    }

  }

  /**
   * Builds organisation funktion options for select.
   */
  private function buildOrganisationFunktionOptions(string $dataType): array {

    $brugerId = $this->getRelevantOrganisationUserId($dataType, FALSE);

    if (NULL === $brugerId) {
      return [];
    }

    if ($dataType === self::DATA_DISPLAY_OPTION_MANAGER) {
      $ids = (array) $this->getRelevantOrganisationUserId($dataType, TRUE);
    }
    else {
      $ids = $this->organisationHelper->getOrganisationFunktioner($brugerId);
    }

    // Make them human-readable.
    $options = [];
    foreach ($ids as $id) {
      $organisationEnhed = $this->organisationHelper->getOrganisationEnhed($id);
      $funktionsNavn = $this->organisationHelper->getFunktionsNavn($id);

      $options[$id] = $organisationEnhed . ', ' . $funktionsNavn;
    }

    return $options;
  }

  /**
   * Updates Funktion dependant sub elements.
   */
  private function updateFunktionSubElements(&$element, $funktionsId) {
    $compositeElements = $this->propertyAccessor->getValue($element, '[#webform_composite_elements]');

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[organisation_enhed][#access]')) {
      $element['#organisation_enhed__value'] = $this->organisationHelper->getOrganisationEnhed($funktionsId);
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[organisation_adresse][#access]')) {
      $element['#organisation_adresse__value'] = $this->organisationHelper->getOrganisationAddress($funktionsId);
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[organisation_niveau_2][#access]')) {
      $element['#organisation_niveau_2__value'] = $this->organisationHelper->getOrganisationEnhedNiveauTo($funktionsId);
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[magistrat][#access]')) {
      $element['#magistrat__value'] = $this->organisationHelper->getPersonMagistrat($funktionsId);
    }
  }

  /**
   * Updates basic sub elements.
   */
  private function updateBasicSubElements(&$element, $dataType) {
    $brugerId = $this->getRelevantOrganisationUserId($dataType, FALSE);

    if (NULL === $brugerId) {
      return;
    }

    $compositeElements = $this->propertyAccessor->getValue($element, '[#webform_composite_elements]');

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[name][#access]')) {
      $element['#name__value'] = $this->organisationHelper->getPersonName($brugerId);
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[email][#access]')) {
      $element['#email__value'] = $this->organisationHelper->getPersonEmail($brugerId);
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[az][#access]')) {
      $element['#az__value'] = $this->organisationHelper->getPersonAZIdent($brugerId);
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[phone][#access]')) {
      $element['#phone__value'] = $this->organisationHelper->getPersonPhone($brugerId);
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[location][#access]')) {
      $element['#location__value'] = $this->organisationHelper->getPersonLocation($brugerId);
    }
  }

  /**
   * Fetches current user organisation user id.
   */
  private function getCurrentUserOrganisationId() {

    $user = $this->entityTypeManager->getStorage('user')->load($this->account->id());

    return $user->hasField('field_organisation_user_id') ? $user->get('field_organisation_user_id')->value : NULL;
  }

  /**
   * Gets relevant organisation bruger or funktions id.
   */
  private function getRelevantOrganisationUserId(string $dataType, bool $returnFunktionsId) {
    $currentUserId = $this->getCurrentUserOrganisationId();

    switch ($dataType) {
      case self::DATA_DISPLAY_OPTION_CURRENT_USER:
        return $currentUserId;

      case self::DATA_DISPLAY_OPTION_MANAGER:
        $managerInfo = $this->organisationHelper->getManagerInfo($currentUserId);

        // @todo Handle multiple managers - for now just pick first one.
        if ($returnFunktionsId) {
          return reset($managerInfo)['funktionsId'];
        }
        else {
          return reset($managerInfo)['brugerId'];
        }
    }

    throw new InvalidSettingException(sprintf('Invalid data display option provided: %s. Allowed types: %s', $dataType, self::DATA_DISPLAY_OPTION_CURRENT_USER . ', ' . self::DATA_DISPLAY_OPTION_MANAGER));
  }

}
