<?php

namespace Drupal\os2forms_organisation\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

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
}
