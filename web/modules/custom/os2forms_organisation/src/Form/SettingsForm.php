<?php

namespace Drupal\os2forms_organisation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_organisation\Helper\CertificateLocatorHelper;
use Drupal\os2forms_organisation\Helper\Settings;
use Drupal\os2forms_organisation\Helper\SettingsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Organisation settings form.
 */
final class SettingsForm extends FormBase {
  use StringTranslationTrait;

  public const AUTHORITY_CVR = 'authority_cvr';
  public const CACHE_EXPIRATION = 'cache_expiration';
  public const CERTIFICATE = 'certificate';
  public const TEST_MODE = 'test_mode';
  public const ORGANISATION_TEST_LEDER_ROLLE_UUID = 'organisation_test_leder_rolle_uuid';
  public const ORGANISATION_PROD_LEDER_ROLLE_UUID = 'organisation_prod_leder_rolle_uuid';
  public const ORGANISATION_SERVICE_ENDPOINT_REFERENCE = 'organisation_service_endpoint_reference';

  /**
   * The settings.
   *
   * @var \Drupal\os2forms_organisation\Helper\Settings
   */
  private SettingsInterface $settings;

  /**
   * The certificate locator helper.
   *
   * @var \Drupal\os2forms_organisation\Helper\CertificateLocatorHelper
   */
  private CertificateLocatorHelper $certificateLocatorHelper;

  /**
   * Constructor.
   */
  public function __construct(SettingsInterface $settings, CertificateLocatorHelper $certificateLocatorHelper) {
    $this->settings = $settings;
    $this->certificateLocatorHelper = $certificateLocatorHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get(Settings::class),
      $container->get(CertificateLocatorHelper::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_organisation_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $defaultValues = $this->settings->getAll();

    $form[self::TEST_MODE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test mode'),
      '#default_value' => $defaultValues[self::TEST_MODE] ?? TRUE,
    ];

    $form[self::AUTHORITY_CVR] = [
      '#type' => 'textfield',
      '#title' => $this->t('Authority CVR'),
      '#required' => TRUE,
      '#default_value' => $defaultValues[self::AUTHORITY_CVR] ?? NULL,
    ];

    $form[self::CERTIFICATE] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Certificate'),
      '#tree' => TRUE,

      'locator_type' => [
        '#type' => 'select',
        '#title' => $this->t('Certificate locator type'),
        '#options' => [
          'azure_key_vault' => $this->t('Azure key vault'),
          'file_system' => $this->t('File system'),
        ],
        '#default_value' => $defaultValues[self::CERTIFICATE]['locator_type'] ?? NULL,
      ],
    ];

    $form[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Azure key vault'),
      '#states' => [
        'visible' => [':input[name="certificate[locator_type]"]' => ['value' => CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT]],
      ],
    ];

    $settings = [
      'tenant_id' => ['title' => $this->t('Tenant id')],
      'application_id' => ['title' => $this->t('Application id')],
      'client_secret' => ['title' => $this->t('Client secret')],
      'name' => ['title' => $this->t('Name')],
      'secret' => ['title' => $this->t('Secret')],
      'version' => ['title' => $this->t('Version')],
    ];

    foreach ($settings as $key => $info) {
      $form[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT][$key] = [
        '#type' => 'textfield',
        '#title' => $info['title'],
        '#description' => $info['description'] ?? NULL,
        '#default_value' => $defaultValues[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT][$key] ?? NULL,
        '#states' => [
          'required' => [':input[name="certificate[locator_type]"]' => ['value' => CertificateLocatorHelper::LOCATOR_TYPE_AZURE_KEY_VAULT]],
        ],
      ];
    }

    $form[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM] = [
      '#type' => 'fieldset',
      '#title' => $this->t('File system'),
      '#states' => [
        'visible' => [':input[name="certificate[locator_type]"]' => ['value' => CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM]],
      ],

      'path' => [
        '#type' => 'textfield',
        '#title' => $this->t('Path'),
        '#default_value' => $defaultValues[self::CERTIFICATE][CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM]['path'] ?? NULL,
        '#states' => [
          'required' => [':input[name="certificate[locator_type]"]' => ['value' => CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM]],
        ],
      ],
    ];

    $form[self::CERTIFICATE]['passphrase'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Passphrase'),
      '#default_value' => $defaultValues[self::CERTIFICATE]['passphrase'] ?? NULL,
    ];

    $form[self::CACHE_EXPIRATION] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cache expiration modifier'),
      '#required' => TRUE,
      '#default_value' => $defaultValues[self::CACHE_EXPIRATION] ?? NULL,
      '#description' => $this->t('Should be in GNU date input format, e.g. "7am, tomorrow 7am". If multiple are provided, they should be separated by comma, and the first upcoming one is used.'),
    ];

    $form[self::ORGANISATION_SERVICE_ENDPOINT_REFERENCE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organisation service endpoint reference'),
      '#required' => TRUE,
      '#default_value' => $defaultValues[self::ORGANISATION_SERVICE_ENDPOINT_REFERENCE] ?? NULL,
      '#description' => $this->t('Probably "http://stoettesystemerne.dk/service/organisation/3", but it may very well change in the future.'),
    ];

    $form[self::ORGANISATION_TEST_LEDER_ROLLE_UUID] = [
      '#type' => 'textfield',
      '#title' => $this->t('Leder rolle uuid test'),
      '#required' => TRUE,
      '#default_value' => $defaultValues[self::ORGANISATION_TEST_LEDER_ROLLE_UUID] ?? NULL,
    ];

    $form[self::ORGANISATION_PROD_LEDER_ROLLE_UUID] = [
      '#type' => 'textfield',
      '#title' => $this->t('Leder rolle uuid produktion'),
      '#required' => TRUE,
      '#default_value' => $defaultValues[self::ORGANISATION_PROD_LEDER_ROLLE_UUID] ?? NULL,
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
    ];

    $form['actions']['testCertificate'] = [
      '#type' => 'submit',
      '#name' => 'testCertificate',
      '#value' => $this->t('Test certificate'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState) {
    $triggeringElement = $formState->getTriggeringElement();
    if ('testCertificate' === ($triggeringElement['#name'] ?? NULL)) {
      return;
    }

    $values = $formState->getValues();

    // Validate cache expiration.
    try {
      $cacheExpirationOptions = explode(',', $values[self::CACHE_EXPIRATION]);
      foreach ($cacheExpirationOptions as $cacheExpirationOption) {
        new \DateTime($cacheExpirationOption);
      }
    }
    catch (\Exception $exception) {
      $formState->setErrorByName(self::CACHE_EXPIRATION, $this->t('Invalid cache expiration: %cache_expiration', ['%cache_expiration' => $values[self::CACHE_EXPIRATION]]));
    }

    if (CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM === $values['certificate']['locator_type']) {
      $path = $values['certificate'][CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM]['path'] ?? NULL;
      if (!file_exists($path)) {
        $formState->setErrorByName('certificate][file_system][path', $this->t('Invalid certificate path: %path', ['%path' => $path]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    $triggeringElement = $formState->getTriggeringElement();
    if ('testCertificate' === ($triggeringElement['#name'] ?? NULL)) {
      $this->testCertificate();
      return;
    }

    $values = $formState->getValues();
    foreach ($this->settings->getKeys() as $key) {
      if (array_key_exists($key, $values)) {
        $this->settings->set($key, $values[$key]);
      }
    }

    $this->messenger()->addStatus($this->t('Settings saved'));

  }

  /**
   * Test certificate.
   */
  private function testCertificate() {
    try {
      $certificateLocator = $this->certificateLocatorHelper->getCertificateLocator();
      $certificateLocator->getCertificates();
      $certificateLocator->getAbsolutePathToCertificate();
      $this->messenger()->addStatus($this->t('Certificate succesfully tested'));
    }
    catch (\Throwable $throwable) {
      $message = $this->t('Error testing certificate: %message', ['%message' => $throwable->getMessage()]);
      $this->messenger()->addError($message);
    }
  }

}
