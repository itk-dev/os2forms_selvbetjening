<?php

namespace Drupal\os2forms_api_request_handler\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\os2forms_api_request_handler\PostHelper;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Post submission job.
 *
 * @AdvancedQueueJobType(
 *   id = "Drupal\os2forms_api_request_handler\Plugin\AdvancedQueue\JobType\PostSubmission",
 *   label = @Translation("Post form submission to API endpoint"),
 * )
 */
class PostSubmission extends JobTypeBase implements ContainerFactoryPluginInterface {
  /**
   * The post helper.
   *
   * @var \Drupal\os2forms_api_request_handler\PostHelper
   */
  private PostHelper $helper;

  /**
   * The submission logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $submissionLogger;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PostHelper $helper,
    LoggerChannelFactoryInterface $loggerFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->helper = $helper;
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
      $container->get(PostHelper::class),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(Job $job): JobResult {
    $payload = $job->getPayload();
    /** @var \Drupal\webform\WebformSubmissionInterface $webformSubmission */
    $webformSubmission = WebformSubmission::load($payload['submission']['id']);
    $logger_context = [
      'channel' => 'webform_submission',
      'webform_submission' => $webformSubmission,
      'operation' => 'response from queue (api request handler)'
    ];

    try {
      $this->helper->post($job->getPayload());
      $this->submissionLogger->log('info', sprintf('The submission #%s was successfully delivered', $webformSubmission->serial()), $logger_context);

      return JobResult::success();
    }
    catch (\Exception $e) {
      $this->submissionLogger->log('error', sprintf('The submission #%s failed (%s)', $webformSubmission->serial(), $e->getMessage()), $logger_context);

      return JobResult::failure($e->getMessage());
    }
  }

}
