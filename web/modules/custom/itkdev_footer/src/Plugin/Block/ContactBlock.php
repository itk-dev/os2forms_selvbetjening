<?php

namespace Drupal\itkdev_footer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Contact' Block.
 *
 * @Block(
 *   id = "contact_block",
 *   admin_label = @Translation("Contact block"),
 *   category = @Translation("ITKDev"),
 * )
 */
class ContactBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['contact_text_first'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First line'),
      '#default_value' => $config['contact_text_first'] ?? '',
    ];

    $form['contact_text_second'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Second line'),
      '#default_value' => $config['contact_text_second'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['contact_text_first'] = $values['contact_text_first'];
    $this->configuration['contact_text_second'] = $values['contact_text_second'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $first_line = !empty($config['contact_text_first']) ? '<p>' . $config['contact_text_first'] . '</p>' : '';
    $second_line = !empty($config['contact_text_second']) ? '<p>' . $config['contact_text_second'] . '</p>' : '';

    return [
      '#markup' => $first_line . $second_line,
    ];
  }

}
