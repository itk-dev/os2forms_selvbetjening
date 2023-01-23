<?php

namespace Drupal\os2forms_get_organized\Plugin\WebformHandler;

use Drupal\advancedqueue\Job;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\os2forms_get_organized\Plugin\AdvancedQueue\JobType\ArchiveDocument;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get Organized Webform Handler.
 *
 * @WebformHandler(
 *   id = "os2forms_get_organized",
 *   label = @Translation("GetOrganized"),
 *   category = @Translation("Web services"),
 *   description = @Translation("Archives response in GetOrganized."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class GetOrganizedWebformHandler extends WebformHandlerBase {
  /**
   * The submission logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $submissionLogger;

  /**
   * Constructs a GetOrganizedWebformHandler object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $loggerFactory, ConfigFactoryInterface $configFactory, RendererInterface $renderer, EntityTypeManagerInterface $entityTypeManager, WebformSubmissionConditionsValidatorInterface $conditionsValidator, WebformTokenManagerInterface $tokenManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->loggerFactory = $loggerFactory;
    $this->configFactory = $configFactory;
    $this->renderer = $renderer;
    $this->entityTypeManager = $entityTypeManager;
    $this->conditionsValidator = $conditionsValidator;
    $this->tokenManager = $tokenManager;
    $this->submissionLogger = $loggerFactory->get('webform_submission');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $elements = $this->getWebform()->getElementsDecodedAndFlattened();

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Choose general settings'),
    ];

    $form['general']['should_be_finalized'] = [
      '#title' => $this->t('Should document be finalized?'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['general']['should_be_finalized'] ?? FALSE,
      '#description' => $this->t('If enabled, documents will be finalized (journaliseret) in GetOrganized.'),
      '#required' => FALSE,
    ];

    $form['general']['attachment_element'] = [
      '#type' => 'select',
      '#title' => $this->t('Attachment element'),
      '#options' => $this->getAvailableElementsByType('webform_entity_print_attachment:pdf', $elements),
      '#default_value' => $this->configuration['general']['attachment_element'] ?? '',
      '#description' => $this->t('Choose the element responsible for creating response attachments.'),
      '#required' => TRUE,
      '#size' => 5,
    ];

    $form['choose_archiving_method'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Choose archiving method'),
    ];

    $form['choose_archiving_method']['archiving_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose method for archiving attachment'),
      '#options' => [
        'archive_to_case_id' => $this->t('GetOrganized case ID'),
        'archive_to_citizen' => $this->t('Citizen CPR number'),
      ],
      '#default_value' => $this->configuration['choose_archiving_method']['archiving_method'] ?? 'archive_to_case_id',
      '#required' => TRUE,
      '#size' => 3,
    ];

    $form['choose_archiving_method']['case_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GetOrganized case ID'),
      '#description' => $this->t('The GetOrganized case that responses should be uploaded to.'),
      '#default_value' => $this->configuration['choose_archiving_method']['case_id'] ?? '',
      '#states' => [
        'visible' => [
          [':input[name="settings[choose_archiving_method][archiving_method]"]' => ['value' => 'archive_to_case_id']],
        ],
        // The only effect this has is showing the required asterisk (*).
        // Actual validation happens in validateConfigurationForm.
        'required' => [
          [':input[name="settings[choose_archiving_method][archiving_method]"]' => ['value' => 'archive_to_case_id']],
        ],
      ],
    ];

    $form['choose_archiving_method']['sub_case_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GetOrganized case title'),
      '#description' => $this->t('The GetOrganized case that responses should be uploaded to. If no case with provided title exists one will be created. If multiple exists nothing will be uploaded.'),
      '#default_value' => $this->configuration['choose_archiving_method']['sub_case_title'] ?? '',
      '#states' => [
        'visible' => [
          [':input[name="settings[choose_archiving_method][archiving_method]"]' => ['value' => 'archive_to_citizen']],
        ],
        // The only effect this has is showing the required asterisk (*).
        // Actual validation happens in validateConfigurationForm.
        'required' => [
          [':input[name="settings[choose_archiving_method][archiving_method]"]' => ['value' => 'archive_to_citizen']],
        ],
      ],
    ];

    $form['choose_archiving_method']['cpr_value_element'] = [
      '#type' => 'select',
      '#title' => $this->t('CPR element'),
      '#options' => $this->getAvailableElementsByType('cpr_value_element', $elements),
      '#default_value' => $this->configuration['choose_archiving_method']['cpr_value_element'] ?? '',
      '#description' => $this->t('Choose the element containing CPR number'),
      '#size' => 5,
      '#states' => [
        'visible' => [
          [':input[name="settings[choose_archiving_method][archiving_method]"]' => ['value' => 'archive_to_citizen']],
        ],
        // The only effect this has is showing the required asterisk (*).
        // Actual validation happens in validateConfigurationForm.
        'required' => [
          [':input[name="settings[choose_archiving_method][archiving_method]"]' => ['value' => 'archive_to_citizen']],
        ],
      ],
    ];

    $form['choose_archiving_method']['cpr_name_element'] = [
      '#type' => 'select',
      '#title' => $this->t('CPR element'),
      '#options' => $this->getAvailableElementsByType('cpr_name_element', $elements),
      '#default_value' => $this->configuration['choose_archiving_method']['cpr_name_element'] ?? '',
      '#description' => $this->t('Choose the element containing CPR name'),
      '#size' => 5,
      '#states' => [
        'visible' => [
          [':input[name="settings[choose_archiving_method][archiving_method]"]' => ['value' => 'archive_to_citizen']],
        ],
        // The only effect this has is showing the required asterisk (*).
        // Actual validation happens in validateConfigurationForm.
        'required' => [
          [':input[name="settings[choose_archiving_method][archiving_method]"]' => ['value' => 'archive_to_citizen']],
        ],
      ],
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['general']['should_be_finalized'] = $form_state->getValue('general')['should_be_finalized'];
    $this->configuration['general']['attachment_element'] = $form_state->getValue('general')['attachment_element'];
    $this->configuration['choose_archiving_method']['archiving_method'] = $form_state->getValue('choose_archiving_method')['archiving_method'];
    $this->configuration['choose_archiving_method']['case_id'] = $form_state->getValue('choose_archiving_method')['case_id'];
    $this->configuration['choose_archiving_method']['cpr_value_element'] = $form_state->getValue('choose_archiving_method')['cpr_value_element'];
    $this->configuration['choose_archiving_method']['cpr_name_element'] = $form_state->getValue('choose_archiving_method')['cpr_name_element'];
    $this->configuration['choose_archiving_method']['sub_case_title'] = $form_state->getValue('choose_archiving_method')['sub_case_title'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $configuration = $form_state->getValues();
    if ($configuration['choose_archiving_method']['archiving_method'] === 'archive_to_case_id') {
      if (empty($configuration['choose_archiving_method']['case_id'])) {
        $form_state->setErrorByName('no_case_id_provided', $this->t('No GetOrganized case ID provided.'));
      }
    }

    if ($configuration['choose_archiving_method']['archiving_method'] === 'archive_to_citizen') {
      if (empty($configuration['choose_archiving_method']['cpr_value_element'])) {
        $form_state->setErrorByName('no_cpr_value_element_selected', $this->t('No CPR value element selected.'));
      }
      if (empty($configuration['choose_archiving_method']['cpr_name_element'])) {
        $form_state->setErrorByName('no_cpr_name_element_selected', $this->t('No CPR name element selected.'));
      }
      if (empty($configuration['choose_archiving_method']['sub_case_title'])) {
        $form_state->setErrorByName('no_sub_case_title', $this->t('No GetOrganized case title provided.'));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $queueStorage = $this->entityTypeManager->getStorage('advancedqueue_queue');
    /** @var \Drupal\advancedqueue\Entity\Queue $queue */
    $queue = $queueStorage->load('get_organized_queue');
    $job = Job::create(ArchiveDocument::class, [
      'submissionId' => $webform_submission->id(),
      'handlerConfiguration' => $this->configuration,
    ]);
    $queue->enqueueJob($job);

    $logger_context = [
      'channel' => 'webform_submission',
      'webform_submission' => $webform_submission,
      'operation' => 'submission queued (get organized handler)',
    ];

    $this->submissionLogger->notice($this->t('Added submission #@serial to queue for processing', ['@serial' => $webform_submission->serial()]), $logger_context);
  }

  /**
   * Get available elements by type.
   */
  private function getAvailableElementsByType(string $type, array $elements): array {
    $attachmentElements = array_filter($elements, function ($element) use ($type) {
      return $type === $element['#type'];
    });

    return array_map(function ($element) {
      return $element['#title'];
    }, $attachmentElements);
  }

}
