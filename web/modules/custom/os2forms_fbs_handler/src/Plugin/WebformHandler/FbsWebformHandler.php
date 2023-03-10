<?php

namespace Drupal\os2forms_fbs_handler\Plugin\WebformHandler;

use Drupal\advancedqueue\Job;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\os2forms_fbs_handler\Plugin\AdvancedQueue\JobType\FbsCreateUser;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fbs Webform Handler.
 *
 * @WebformHandler(
 *   id = "os2forms_fbs",
 *   label = @Translation("FBS"),
 *   category = @Translation("Web services"),
 *   description = @Translation("Adds user to fbs"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
final class FbsWebformHandler extends WebformHandlerBase {
  /**
   * The submission logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $submissionLogger;

  /**
   * Constructs an FbsWebformHandler object.
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
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $queueStorage = $this->entityTypeManager->getStorage('advancedqueue_queue');
    /** @var \Drupal\advancedqueue\Entity\Queue $queue */
    $queue = $queueStorage->load('os2forms_fbs_handler');
    $job = Job::create(FbsCreateUser::class, [
      'submissionId' => $webform_submission->id(),
      'handlerConfiguration' => $this->configuration,
    ]);
    $queue->enqueueJob($job);

    $logger_context = [
      'handler_id' => 'os2forms_fbs',
      'channel' => 'webform_submission',
      'webform_submission' => $webform_submission,
      'operation' => 'submission queued',
    ];

    $this->submissionLogger->notice($this->t('Added submission #@serial to queue for processing', ['@serial' => $webform_submission->serial()]), $logger_context);
  }
}