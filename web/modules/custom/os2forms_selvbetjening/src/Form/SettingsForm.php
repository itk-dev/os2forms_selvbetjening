<?php

namespace Drupal\os2forms_selvbetjening\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_selvbetjening\Helper\Settings;
use Drupal\os2forms_selvbetjening\Helper\WebformConfigurationExporter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface as OptionsResolverException;

/**
 * Organisation settings form.
 */
final class SettingsForm extends FormBase {
  use StringTranslationTrait;

  public const SELVBETJENING_WEBFORM_EXPORT_FILENAME = 'selvbetjening_webform_export_filename';

  /**
   * Constructor.
   */
  public function __construct(private readonly Settings $settings, private readonly WebformConfigurationExporter $webformConfigurationExporter) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): SettingsForm {
    return new static(
      $container->get(Settings::class),
      $container->get(WebformConfigurationExporter::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_selvbetjening_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $webformExportFilename = $this->settings->getWebformExportFilename();
    $form[self::SELVBETJENING_WEBFORM_EXPORT_FILENAME] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filename for webform csv export'),
      '#required' => TRUE,
      '#default_value' => !empty($webformExportFilename) ? $webformExportFilename : NULL,
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
    ];

    $form['actions']['exportWebformConfiguration'] = [
      '#type' => 'submit',
      '#name' => 'exportWebformConfiguration',
      '#value' => $this->t('Export webform configuration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $formState): void {
    $triggeringElement = $formState->getTriggeringElement();
    if ('exportWebformConfiguration' === ($triggeringElement['#name'] ?? NULL)) {
      $this->exportWebformConfiguration($formState->getValue(self::SELVBETJENING_WEBFORM_EXPORT_FILENAME));
      return;
    }

    try {
      $settings[self::SELVBETJENING_WEBFORM_EXPORT_FILENAME] = $formState->getValue(self::SELVBETJENING_WEBFORM_EXPORT_FILENAME);

      $this->settings->setSettings($settings);
      $this->messenger()->addStatus($this->t('Settings saved'));
    }
    catch (OptionsResolverException $exception) {
      $this->messenger()->addError($this->t('Settings not saved (@message)', ['@message' => $exception->getMessage()]));
    }

    $this->messenger()->addStatus($this->t('Settings saved'));

  }

  /**
   * Export webform configuration.
   */
  private function exportWebformConfiguration(string $filename): void {
    try {
      $this->webformConfigurationExporter->extractWebformConfiguration($filename);
      $this->messenger()->addStatus($this->t('Data successfully exported'));
    }
    catch (\Throwable $throwable) {
      $message = $this->t('Error exporting webform configuration: %message', ['%message' => $throwable->getMessage()]);
      $this->messenger()->addError($message);
    }
  }

}
