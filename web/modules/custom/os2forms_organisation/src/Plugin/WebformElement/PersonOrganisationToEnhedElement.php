<?php

namespace Drupal\os2forms_organisation\Plugin\WebformElement;

use Drupal\os2forms_organisation\Helper\Helper;
use Drupal\webform\Plugin\WebformElement\TextField;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Person organisation to enhed element.
 *
 * @WebformElement(
 *   id = "person_organisation_to_enhed_element",
 *   label = @Translation("Person Organisation Two Enhed Element"),
 *   description = @Translation("Person Organisation to Enhed Element description"),
 *   category = @Translation("Organisation")
 * )
 */
class PersonOrganisationToEnhedElement extends TextField {

  /**
   * Organisation Helper.
   *
   * @var \Drupal\os2forms_organisation\Helper\Helper
   */
  protected Helper $helper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->helper = $container->get(Helper::class);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function finalize(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $element['#value'] = $this->helper->getOrganisationEnhedNiveauTo();
    parent::finalize($element, $webform_submission);
  }

}
