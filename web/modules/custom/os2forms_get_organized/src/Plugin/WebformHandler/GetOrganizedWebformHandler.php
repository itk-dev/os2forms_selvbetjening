<?php

namespace Drupal\os2forms_get_organized\Plugin\WebformHandler;

use Drupal\advancedqueue\Job;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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

    $form['case_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GetOrganized case ID'),
      '#description' => $this->t('The GetOrganized case that responses should be uploaded to.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['case_id'] ?? '',
    ];

    $form['should_be_finalized'] = [
      '#title' => $this->t('Should document be finalized?'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['should_be_finalized'] ?? FALSE,
      '#description' => $this->t('If enabled, documents will be finalized (journaliseret) in GetOrganized.'),
      '#required' => FALSE,
    ];

    $form['attachment_element'] = [
      '#type' => 'select',
      '#title' => $this->t('Attachment element'),
      '#options' => $this->getAvailableAttachmentElements($this->getWebform()->getElementsDecodedAndFlattened()),
      '#default_value' => $this->configuration['attachment_element'] ?? '',
      '#description' => $this->t('Choose the element responsible for creating response attachments.'),
      '#required' => TRUE,
      '#size' => 5,
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['case_id'] = $form_state->getValue('case_id');
    $this->configuration['attachment_element'] = $form_state->getValue('attachment_element');
    $this->configuration['should_be_finalized'] = $form_state->getValue('should_be_finalized');
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
  }

  /**
   * Get available attachment elements.
   */
  private function getAvailableAttachmentElements(array $elements): array {
    $attachmentElements = array_filter($elements, function ($element) {
      return 'webform_entity_print_attachment:pdf' === $element['#type'];
    });

    return array_map(function ($element) {
      return $element['#title'];
    }, $attachmentElements);
  }

}
