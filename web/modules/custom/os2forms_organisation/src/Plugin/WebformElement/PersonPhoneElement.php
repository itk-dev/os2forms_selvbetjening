<?php

namespace Drupal\os2forms_organisation\Plugin\WebformElement;

use Drupal\os2forms_organisation\Helper\Helper;
use Drupal\webform\Plugin\WebformElement\TextField;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Person phone element.
 *
 * @WebformElement(
 *   id = "person_phone_element",
 *   label = @Translation("Person Phone Element"),
 *   description = @Translation("Person Phone Element description"),
 *   category = @Translation("Organisation")
 * )
 */
class PersonPhoneElement extends TextField {

  /**
   * Organisation Helper.
   *
   * @var \Drupal\os2forms_organisation\Helper\Helper
   */
  protected Helper $helper;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->helper = $container->get(Helper::class);

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function finalize(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $element['#value'] = $this->helper->getPersonPhone();
    parent::finalize($element, $webform_submission);
  }

}
