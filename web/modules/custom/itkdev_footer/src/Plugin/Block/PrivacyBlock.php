<?php

namespace Drupal\itkdev_footer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Privacy' Block.
 *
 * @Block(
 *   id = "privacy_block",
 *   admin_label = @Translation("Privacy block"),
 *   category = @Translation("ITKDev"),
 * )
 */
class PrivacyBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['privacy_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Privacy url'),
      '#description' => $this->t('A url to a page describing the sites privacy policy.'),
      '#default_value' => $config['privacy_url'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['privacy_url'] = $values['privacy_url'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $link = !empty($config['privacy_url']) ? '<ul class="nav"><li><a href="' . $config['privacy_url'] . '">' . $this->t('Privacy policy') . '</a></li></ul>' : '';
    return [
      '#markup' => $link,
    ];
  }

}
