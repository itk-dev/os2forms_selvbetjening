<?php

namespace Drupal\os2forms_organisation\Helper;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class FormHelper
{

  /**
   * The Organisation Helper.
   *
   * @var \Drupal\os2forms_organisation\Helper\Helper
   */
  private Helper $helper;

  /**
   * Property accessor.
   *
   * @var \Symfony\Component\PropertyAccess\PropertyAccessor
   */
  private PropertyAccessor $propertyAccessor;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private RouteMatchInterface $routeMatch;

  /**
   * Constructs a FormHelper.
   */
  public function __construct(Helper $helper, PropertyAccessor $propertyAccessor, RouteMatchInterface $routeMatch) {
    $this->helper = $helper;
    $this->propertyAccessor = $propertyAccessor;
    $this->routeMatch = $routeMatch;
  }

  /**
   * Allows altering of forms.
   */
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {

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

    $elements = $form['elements'];

    foreach ($elements as $element) {
      if (is_array($element) && 'mine_organisations_data_element' === ($element['#type'] ?? NULL)) {

        // Notice that this takes the elements from the form.
        $compositeElement = &NestedArray::getValue($form['elements'], $element['#webform_parents']);

        $this->updateBasicSubElements($compositeElement);

        $options = $this->buildOrganisationFunktionOptions();

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
   * Updates sub elements.
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
