<?php

namespace Drupal\os2forms_selvbetjening\Helper;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\os2forms_selvbetjening\Exception\InvalidSettingException;
use Drupal\os2forms_selvbetjening\Form\SettingsForm;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * General settings for os2forms_selvbetjening.
 */
final class Settings {
  /**
   * The store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  private KeyValueStoreInterface $store;

  /**
   * The key value collection name.
   *
   * @var string
   */
  private $collection = 'os2forms_selvbetjening.';

  /**
   * The constructor.
   */
  public function __construct(KeyValueFactoryInterface $keyValueFactory) {
    $this->store = $keyValueFactory->get($this->collection);
  }

  /**
   * Get webform export filename.
   */
  public function getWebformExportFilename(): string {
    return $this->get(SettingsForm::SELVBETJENING_WEBFORM_EXPORT_FILENAME, '');
  }

  /**
   * Get webform export template option.
   */
  public function getIncludeTemplateWebforms(): bool {
    return (bool) $this->get(SettingsForm::SELVBETJENING_WEBFORM_EXPORT_INCLUDE_TEMPLATES, TRUE);
  }

  /**
   * Get webform export archived option.
   */
  public function getIncludeArchivedWebforms(): bool {
    return (bool) $this->get(SettingsForm::SELVBETJENING_WEBFORM_EXPORT_INCLUDE_ARCHIVED, TRUE);
  }

  /**
   * Get a setting value.
   *
   * @param string $key
   *   The key.
   * @param mixed|null $default
   *   The default value.
   *
   * @return mixed
   *   The setting value.
   */
  private function get(string $key, $default = NULL) {
    $resolver = $this->getSettingsResolver();
    if (!$resolver->isDefined($key)) {
      throw new InvalidSettingException(sprintf('Setting %s is not defined', $key));
    }

    return $this->store->get($key, $default);
  }

  /**
   * Set settings.
   *
   * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
   *
   * @phpstan-param array<string, mixed> $settings
   */
  public function setSettings(array $settings): self {
    $settings = $this->getSettingsResolver()->resolve($settings);
    foreach ($settings as $key => $value) {
      $this->store->set($key, $value);
    }

    return $this;
  }

  /**
   * Get settings resolver.
   */
  private function getSettingsResolver(): OptionsResolver {
    return (new OptionsResolver())
      ->setDefaults([
        SettingsForm::SELVBETJENING_WEBFORM_EXPORT_FILENAME => '',
        SettingsForm::SELVBETJENING_WEBFORM_EXPORT_INCLUDE_TEMPLATES => TRUE,
        SettingsForm::SELVBETJENING_WEBFORM_EXPORT_INCLUDE_ARCHIVED => TRUE,
      ]);
  }

}
