<?php

namespace Drupal\os2forms_api_request_handler\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\os2forms_api_request_handler\PostHelper;
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
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PostHelper $helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(PostHelper::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(Job $job): JobResult {
    try {
      $this->helper->post($job->getPayload());

      return JobResult::success();
    }
    catch (\Exception $e) {
      return JobResult::failure($e->getMessage());
    }
  }

}
