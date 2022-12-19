<?php

namespace Drupal\os2forms_organisation\Plugin\WebformElement;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->helper = $container->get(Helper::class);
    $instance->propertyAccessor = $container->get('property_accessor');

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
   * {@inheritdoc}
   */
  public function finalize(array &$element, WebformSubmissionInterface $webform_submission = NULL) {

    $values = [];

    // Elements determined by organisation funktion are handled in alterForm.
    if (FALSE !== $this->propertyAccessor->getValue($element, '[#name__access]')) {
      $values['name'] = $this->helper->getPersonName();
    }

    if (FALSE !== $this->propertyAccessor->getValue($element, '[#email__access]')) {
      $values['email'] = $this->helper->getPersonEmail();
    }

    if (FALSE !== $this->propertyAccessor->getValue($element, '[#az__access]')) {
      $values['az'] = $this->helper->getPersonAZIdent();
    }

    if (FALSE !== $this->propertyAccessor->getValue($element, '[#phone__access]')) {
      $values['phone'] = $this->helper->getPersonPhone();
    }

    if (FALSE !== $this->propertyAccessor->getValue($element, '[#location__access]')) {
      $values['location'] = $this->helper->getPersonLocation();
    }

    $element['#value'] = $values;

    parent::finalize($element, $webform_submission);
  }

  /**
   * Alters form.
   */
  public function alterForm(array &$element, array &$form, FormStateInterface $form_state) {
    if ('mine_organisations_data_element' === $element['#type']) {

      // Notice that this takes the elements from the form.
      $compositeElement = &NestedArray::getValue($form['elements'], $element['#webform_parents']);

      $options = $this->buildOrganisationFunktionOptions();

      // If there is only one organisation funktion (ansættelse),
      // preselect it and fill out the elements that require it.
      // @todo Handle multiple ansættelser with js
      if (count($options) === 1) {
        $key = array_key_first($options);
        $compositeElement['#organisations_funktion__value'] = $key;

        $this->updateSubElements($compositeElement, $key);
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
   * Updates sub elements.
   */
  private function updateSubElements(&$element, $id) {
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

}
