<?php

namespace Drupal\os2forms_get_organized\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\os2forms_get_organized\Helper\ArchiveHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Archive document job.
 *
 * @AdvancedQueueJobType(
 *   id = "Drupal\os2forms_get_organized\Plugin\AdvancedQueue\JobType\ArchiveDocument",
 *   label = @Translation("Archive document in GetOrganized"),
 * )
 */
class ArchiveDocument extends JobTypeBase implements ContainerFactoryPluginInterface
{
  /**
   * The archiving helper.
   *
   * @var ArchiveHelper
   */
  private ArchiveHelper $helper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('os2forms_get_organized.archive_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ArchiveHelper $helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->helper = $helper;
  }

  public function process(Job $job): JobResult
  {
    $payload = $job->getPayload();

    try {
      $this->helper->archive($payload['submissionId'], $payload['handlerConfiguration']);

      return JobResult::success();
    } catch (\Exception $e) {
      return JobResult::failure($e->getMessage());
    }
  }
}
