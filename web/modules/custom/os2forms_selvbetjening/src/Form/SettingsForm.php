<?php

namespace Drupal\os2forms_selvbetjening\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_selvbetjening\Helper\WebformConfigurationExporter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Organisation settings form.
 */
final class SettingsForm extends FormBase {
  use StringTranslationTrait;

  private const SELVBETJENING_WEBFORM_EXPORT_FILENAME = 'selvbetjening_webform_export_filename';
  private const SELVBETJENING_WEBFORM_EXPORT_FILENAME_DEFAULT = 'webform_config.csv';
  private const SELVBETJENING_WEBFORM_EXPORT_INCLUDE_TEMPLATES = 'selvbetjening_webform_export_include_templates';
  private const SELVBETJENING_WEBFORM_EXPORT_INCLUDE_ARCHIVED = 'selvbetjening_webform_export_include_archived';

  /**
   * Constructor.
   */
  public function __construct(private readonly WebformConfigurationExporter $webformConfigurationExporter) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): SettingsForm {
    return new static(
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

    $form[self::SELVBETJENING_WEBFORM_EXPORT_FILENAME] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filename for webform csv export'),
      '#description' => $this->t('Should contain .csv, e.g. %default', ['%default' => self::SELVBETJENING_WEBFORM_EXPORT_FILENAME_DEFAULT]),
      '#required' => TRUE,
      '#default_value' => self::SELVBETJENING_WEBFORM_EXPORT_FILENAME_DEFAULT,
    ];

    $form[self::SELVBETJENING_WEBFORM_EXPORT_INCLUDE_TEMPLATES] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include template webforms'),
      '#default_value' => TRUE,
    ];

    $form[self::SELVBETJENING_WEBFORM_EXPORT_INCLUDE_ARCHIVED] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include archived webforms'),
      '#default_value' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';

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

    $filename = (string) $formState->getValue(self::SELVBETJENING_WEBFORM_EXPORT_FILENAME);
    $includeTemplateWebforms = (bool) $formState->getValue(self::SELVBETJENING_WEBFORM_EXPORT_INCLUDE_TEMPLATES);
    $includeArchivedWebforms = (bool) $formState->getValue(self::SELVBETJENING_WEBFORM_EXPORT_INCLUDE_ARCHIVED);

    try {
      $this->webformConfigurationExporter->extractWebformConfiguration($filename, $includeTemplateWebforms, $includeArchivedWebforms);
    }
    catch (\Throwable $throwable) {
      $message = $this->t('Error exporting webform configuration: %message', ['%message' => $throwable->getMessage()]);
      $this->messenger()->addError($message);
    }
  }

}
