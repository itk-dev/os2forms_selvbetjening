<?php

namespace Drupal\os2forms_get_organized\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\os2forms_get_organized\Helper\ArchiveHelper;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Archive document job.
 *
 * @AdvancedQueueJobType(
 *   id = "Drupal\os2forms_get_organized\Plugin\AdvancedQueue\JobType\ArchiveDocument",
 *   label = @Translation("Archive document in GetOrganized"),
 * )
 */
class ArchiveDocument extends JobTypeBase implements ContainerFactoryPluginInterface {
  /**
   * The archiving helper.
   *
   * @var \Drupal\os2forms_get_organized\Helper\ArchiveHelper
   */
  private ArchiveHelper $helper;

  /**
   * The submission logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $submissionLogger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('os2forms_get_organized.archive_helper'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ArchiveHelper $helper,
    LoggerChannelFactoryInterface $loggerFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->helper = $helper;
    $this->submissionLogger = $loggerFactory->get('webform_submission');
  }

  /**
   * Processes the ArchiveDocument job.
   */
  public function process(Job $job): JobResult {
    try {
      $payload = $job->getPayload();

      /** @var \Drupal\webform\WebformSubmissionInterface $webformSubmission */
      $webformSubmission = WebformSubmission::load($payload['submissionId']);
      $logger_context = [
        'channel' => 'webform_submission',
        'webform_submission' => $webformSubmission,
        'operation' => 'response from queue (get organized handler)',
      ];

      try {
        $this->helper->archive($payload['submissionId'], $payload['handlerConfiguration']);
        $this->submissionLogger->notice($this->t('The submission #@serial was successfully delivered', ['@serial' => $webformSubmission->serial()]), $logger_context);

        return JobResult::success();
      }
      catch (\Exception $e) {
        $this->submissionLogger->error($this->t('The submission #@serial failed (@message)', [
          '@serial' => $webformSubmission->serial(),
          '@message' => $e->getMessage(),
        ]), $logger_context);

        return JobResult::failure($e->getMessage());
      }
    }
    catch (\Exception $e) {
      return JobResult::failure($e->getMessage());
    }
  }
}
