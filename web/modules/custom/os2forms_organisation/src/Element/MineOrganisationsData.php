<?php

namespace Drupal\os2forms_organisation\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a webform element for personal organisation data (SF1500).
 *
 * @FormElement("mine_organisations_data_element")
 */
class MineOrganisationsData extends WebformCompositeBase {
  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];

    $elements['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name'),
    ];

    $elements['email'] = [
      '#type' => 'textfield',
      '#title' => t('Email'),
    ];

    $elements['az'] = [
      '#type' => 'textfield',
      '#title' => t('AZ-ident'),
    ];

    $elements['phone'] = [
      '#type' => 'textfield',
      '#title' => t('Phone'),
    ];

    $elements['location'] = [
      '#type' => 'textfield',
      '#title' => t('Location'),
    ];

    $elements['organisations_funktion'] = [
      '#type' => 'select',
      '#title' => t('Organisations funktion'),
      '#options' => [],
    ];

    $elements['organisation_enhed'] = [
      '#type' => 'textfield',
      '#title' => t('Organisation enhed'),
    ];

    $elements['organisation_adresse'] = [
      '#type' => 'textfield',
      '#title' => t('Organisation enheds adresse'),
    ];

    $elements['organisation_niveau_2'] = [
      '#type' => 'textfield',
      '#title' => t('Organisation enhed niveau to'),
    ];

    $elements['magistrat'] = [
      '#type' => 'textfield',
      '#title' => t('Magistrat'),
    ];

    return $elements;
  }

}
