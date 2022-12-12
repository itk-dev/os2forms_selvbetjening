<?php

namespace Drupal\os2forms_organisation\Plugin\WebformElement;

use Drupal\os2forms_organisation\Helper\Helper;
use Drupal\webform\Plugin\WebformElement\TextField;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Person magistrat element.
 *
 * @WebformElement(
 *   id = "person_magistrat_element",
 *   label = @Translation("Person Magistrat Element"),
 *   description = @Translation("Person Magistrat Element description"),
 *   category = @Translation("Organisation")
 * )
 */
class PersonMagistratElement extends TextField {

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

    $instance->helper = \Drupal::getContainer()->get(Helper::class);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function finalize(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $element['#value'] = $this->helper->getPersonMagistrat();
    parent::finalize($element, $webform_submission);
  }

}
