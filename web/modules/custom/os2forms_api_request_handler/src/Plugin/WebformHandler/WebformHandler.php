<?php

namespace Drupal\os2forms_api_request_handler\Plugin\WebformHandler;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\os2forms_api_request_handler\Plugin\AdvancedQueue\JobType\PostSubmission;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * API webform handler.
 *
 * @WebformHandler(
 *   id = "os2forms_api_request_handler",
 *   label = @Translation("API request handler"),
 *   category = @Translation("Web services"),
 *   description = @Translation("Send submission to an API endpoint."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class WebformHandler extends WebformHandlerBase {
  /**
   * The queue id.
   *
   * @var string
   */
  private string $queueId = 'os2forms_api_request_handler';

  /**
   * The submission logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $submissionLogger;

  /**
   * Constructor.
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
    if (NULL === $this->getQueue()) {
      $form['queue_message'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [$this->t('Cannot get queue @queue_id', ['@queue_id' => $this->queueId])],
        ],
      ];
    }

    $form['api_url'] = [
      '#type' => 'textarea',
      '#title' => $this->t('API url'),
      '#description' => $this->t('The API url. For testing, an api url can be obtained from <a href="https://webhook.site">Webhook.site</a>.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['api_url'] ?? '',
    ];

    $form['api_authorization_header'] = [
      '#type' => 'textarea',
      '#title' => $this->t('API authorization header'),
      '#description' => $this->t('The API authorization header value. Will be sent in an authorization header: <code>Authorization: «value»</code>.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['api_authorization_header'] ?? '',
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['api_url'] = $form_state->getValue('api_url');
    $this->configuration['api_authorization_header'] = $form_state->getValue('api_authorization_header');
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $submission, $update = TRUE) {
    $queue = $this->getQueue();
    if (NULL === $queue) {
      $this->loggerFactory->get('os2forms_api_request_handler')->error(sprintf('Cannot get %s queue', $this->queueId));
      return;
    }

    $job = Job::create(PostSubmission::class, [
      'submission' => [
        'id' => $submission->id(),
      ],
      'handler' => [
        'configuration' => $this->configuration,
      ],
    ]);
    $queue->enqueueJob($job);

    $logger_context = [
      'channel' => 'webform_submission',
      'webform_submission' => $submission,
      'operation' => 'submission queued',
    ];

    $this->submissionLogger->log('info', sprintf('Added submission #%s to queue for processing', $submission->serial()), $logger_context);
  }

  /**
   * Get queue.
   */
  private function getQueue(): ?Queue {
    $queueStorage = $this->entityTypeManager->getStorage('advancedqueue_queue');
    /** @var ?\Drupal\advancedqueue\Entity\Queue $queue */
    $queue = $queueStorage->load($this->queueId);

    return $queue;
  }

}
