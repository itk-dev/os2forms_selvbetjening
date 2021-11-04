<?php

namespace Drupal\os2forms_user_field_lookup\Plugin\WebformElement;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\Checkbox;

/**
 * User field element.
 *
 * @WebformElement(
 *   id = "user_field_element_checkbox",
 *   label = @Translation("User Field Element (checkbox)"),
 *   description = @Translation("User Field Element (checkbox) description"),
 *   category = @Translation("User fields")
 * )
 */
class UserFieldElementCheckbox extends Checkbox {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'readonly' => TRUE,
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$element, array &$form, FormStateInterface $form_state) {
    if ($fieldName = $element['#os2forms_user_field_lookup_field_name'] ?? NULL) {
      if ($this->currentUser->isAuthenticated()) {
        /** @var \Drupal\user\Entity\User $user */
        $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
        if ($user->hasField($fieldName)) {
          $value = $user->get($fieldName)->value;
          $element['#value'] = (bool) $value;
          NestedArray::setValue($form['elements'], $element['#webform_parents'], $element);
        }
      }
    }
  }

}
