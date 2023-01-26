<?php

namespace Drupal\os2forms_organisation\Helper;

use Drupal\Core\State\StateInterface;
use Drupal\os2forms_organisation\Exception\InvalidSettingException;

/**
 * General settings for os2forms_organisation.
 */
final class Settings implements SettingsInterface {
  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  private StateInterface $state;

  /**
   * The key prefix.
   *
   * @var string
   */
  private $keyPrefix = 'os2forms_organisation.';

  /**
   * The setting keys.
   *
   * @var array|string[]
   */
  private array $keys = [
    'test_mode',
    'authority_cvr',
    'certificate',
    'cache_expiration',
    'organisation_service_endpoint_reference',
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll(): array {
    $values = $this->state->getMultiple(array_map([$this, 'buildKey'], $this->keys));

    $vals = [];
    foreach ($values as $key => $value) {
      $vals[$this->unbuildKey($key)] = $value;
    }

    return $vals;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeys(): array {
    return $this->keys;
  }

  /**
   * {@inheritdoc}
   */
  public function get(string $key, $default = NULL) {
    return $this->state->get($this->buildKey($key), $default);
  }

  /**
   * {@inheritdoc}
   */
  public function set(string $key, $value) {
    $this->state->set($this->buildKey($key), $value);
  }

  /**
   * Build key.
   */
  private function buildKey(string $key) {
    if (!in_array($key, $this->keys, TRUE)) {
      throw new InvalidSettingException(sprintf('Invalid setting: %s', $key));
    }
    return $this->keyPrefix . $key;
  }

  /**
   * Unbuild key.
   */
  private function unbuildKey(string $key) {
    return 0 === strpos($key, $this->keyPrefix) ? substr($key, strlen($this->keyPrefix)) : $key;
  }

}
