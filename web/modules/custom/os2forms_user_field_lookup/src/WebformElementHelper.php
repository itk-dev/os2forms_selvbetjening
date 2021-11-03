<?php

namespace Drupal\os2forms_user_field_lookup;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;

/**
 * Webform element helper with webform element hook implementations.
 */
class WebformElementHelper {
  use StringTranslationTrait;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * Constructor.
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager) {
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Implements hook_webform_element_default_properties_alter().
   */
  public function alterDefaultProperties(array &$properties, array &$definition) {
    if ('os2forms_user_field_lookup' === ($definition['provider'] ?? NULL)) {
      $properties['os2forms_user_field_lookup_field_name'] = '';
    }
  }

  /**
   * Implements hook_webform_element_translatable_properties_alter().
   */
  public function alterTranslatableProperties(array &$properties, array &$definition) {
    // Make the custom data property translatable.
    $properties[] = 'os2forms_user_field_lookup_field_name';
  }

  /**
   * Implements hook_webform_element_configuration_form_alter().
   */
  public function alterConfigurationForm(&$form, FormStateInterface $form_state) {
    /** @var Drupal\webform_ui\Form\WebformUiElementEditForm $formObject */
    $formObject = $form_state->getFormObject();
    $elementPlugin = $formObject->getWebformElementPlugin();
    $pluginDefinition = $elementPlugin->getPluginDefinition();

    // Get some built-in user fields plus all custom fields.
    $userFieldDefinitions = array_filter(
      $this->entityFieldManager->getFieldDefinitions('user', 'user'),
      static function (FieldDefinitionInterface $field) {
        return in_array($field->getName(), ['name', 'mail'], TRUE)
          || $field instanceof FieldConfig;
      }
    );
    $options = array_map(static function (FieldDefinitionInterface $field) {
      return $field->getLabel();
    }, $userFieldDefinitions);

    if ('os2forms_user_field_lookup' === ($pluginDefinition['provider'] ?? NULL)) {
      $form['element']['os2forms_user_field_lookup_field_name'] = [
        '#type' => 'select',
        '#title' => $this->t('User field name'),
        '#required' => TRUE,
        '#options' => $options,
        '#empty_option' => $this->t('- Select -'),
      ];
    }
  }

}
