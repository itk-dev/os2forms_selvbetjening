<?php

namespace Drupal\os2forms_failed_jobs\Plugin\views\field;

use Drupal\advancedqueue\Job;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to render retry operation for a given job.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("advancedqueue_job_retry_operation")
 */
class RetryOperation extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['state'] = 'state';
    $this->additional_fields['queue_id'] = 'queue_id';
    $this->additional_fields['job_id'] = 'job_id';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('administer advancedqueue');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function render(ResultRow $values) {
    $operations = [];

    $state = $this->getValue($values, 'state');
    $queue_id = $this->getValue($values, 'queue_id');
    $job_id = $this->getValue($values, 'job_id');

    if ($state === Job::STATE_FAILURE) {
      $operations['retry'] = [
        'title' => $this->t('Retry'),
        'weight' => -10,
        'url' => Url::fromRoute('advancedqueue.job.retry', [
          'advancedqueue_queue' => $queue_id,
          'job_id' => $job_id,
        ]),
      ];
    }

    $renderer = $this->getRenderer();
    $renderArray = [
      '#type' => 'operations',
      '#links' => $operations,
    ];

    return $renderer->render($renderArray);
  }

}
