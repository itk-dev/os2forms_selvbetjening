<?php

namespace Drupal\os2forms_organisation\Plugin\WebformElement;

use Drupal\os2forms_organisation\Helper\Helper;
use Drupal\webform\Plugin\WebformElement\TextField;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Person email element.
 *
 * @WebformElement(
 *   id = "person_email_element",
 *   label = @Translation("Person Email Element"),
 *   description = @Translation("Person Email Element description"),
 *   category = @Translation("Organisation")
 * )
 */
class PersonEmailElement extends TextField {

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

    $instance->helper = $container->get('organisation.helper');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function finalize(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $element['#value'] = $this->helper->getPersonEmail();
    parent::finalize($element, $webform_submission);
  }

}
