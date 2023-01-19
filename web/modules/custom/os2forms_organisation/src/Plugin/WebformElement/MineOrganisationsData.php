<?php

namespace Drupal\os2forms_organisation\Plugin\WebformElement;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\os2forms_organisation\Helper\Helper;
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

  /**
   * Organisation Helper.
   *
   * @var \Drupal\os2forms_organisation\Helper\Helper
   */
  protected Helper $helper;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->helper = $container->get(Helper::class);
    $instance->propertyAccessor = $container->get('property_accessor');
    $instance->routeMatch = $container->get('current_route_match');


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

      $options = $this->buildOrganisationFunktionOptions();

      $this->updateBasicSubElements($compositeElement);

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
  private function buildOrganisationFunktionOptions(): array {

    $ids = $this->helper->getOrganisationFunktioner();

    if (!is_array($ids)) {
      $ids = [$ids];
    }

    // Make them human-readable.
    $options = [];
    foreach ($ids as $id) {
      $organisationEnhed = $this->helper->getOrganisationEnhed($id);
      $funktionsNavn = $this->helper->getFunktionsNavn($id);

      $options[$id] = $organisationEnhed . ', ' . $funktionsNavn;
    }

    return $options;
  }

  /**
   * Updates Funktion dependant sub elements.
   */
  private function updateFunktionSubElements(&$element, $id) {
    $compositeElements = $this->propertyAccessor->getValue($element, '[#webform_composite_elements]');

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[organisation_enhed][#access]')) {
      $element['#organisation_enhed__value'] = $this->helper->getOrganisationEnhed($id);
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[organisation_adresse][#access]')) {
      $element['#organisation_adresse__value'] = $this->helper->getOrganisationAddress($id);
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[organisation_niveau_2][#access]')) {
      $element['#organisation_niveau_2__value'] = $this->helper->getOrganisationEnhedNiveauTo($id);
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[magistrat][#access]')) {
      $element['#magistrat__value'] = $this->helper->getPersonMagistrat($id);
    }
  }

  private function updateBasicSubElements(&$element)
  {
    $compositeElements = $this->propertyAccessor->getValue($element, '[#webform_composite_elements]');

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[name][#access]')) {
      $element['#name__value'] = $this->helper->getPersonName();
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[email][#access]')) {
      $element['#email__value'] = $this->helper->getPersonEmail();
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[az][#access]')) {
      $element['#az__value'] = $this->helper->getPersonAZIdent();
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[phone][#access]')) {
      $element['#phone__value'] = $this->helper->getPersonPhone();
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[location][#access]')) {
      $element['#location__value'] = $this->helper->getPersonLocation();
    }
  }
}
