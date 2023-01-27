<?php

namespace Drupal\os2forms_failed_jobs\Controller;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\os2forms_failed_jobs\Helper\Os2formsFailedJobsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ContestantsController.
 */
class Os2formsFailedJobsController extends ControllerBase {

  /**
   * Failed jobs helper.
   *
   * @var \Drupal\os2forms_failed_jobs\Helper\Os2formsFailedJobsHelper
   */
  protected Os2formsFailedJobsHelper $failedJobsHelper;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Failed jobs constructor.
   */
  public function __construct(EntityTypeManager $entityTypeManager, Os2formsFailedJobsHelper $failedJobsHelper, RequestStack $requestStack) {
    $this->entityTypeManager = $entityTypeManager;
    $this->failedJobsHelper = $failedJobsHelper;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): Os2formsFailedJobsController {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('Drupal\os2forms_failed_jobs\Helper\Os2formsFailedJobsHelper'),
      $container->get('request_stack'),
    );
  }

  /**
   * Renders the failed jobs view page.
   *
   * @return array
   *   The renderable array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function render(): array {
    $view = Views::getView('os2forms_failed_jobs');
    $view->setDisplay('block_1');
    // Add custom argument that the views_ui cannot provide.
    $formId = $this->requestStack->getCurrentRequest()->get('webform')->id();
    $view->setArguments($this->getQueueJobIds($formId));
    $view->execute();
    $rendered = $view->render();
    $output = \Drupal::service('renderer')->render($rendered);

    return [
      ['#markup' => $output]
    ];
  }

  /**
   * Add title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function title(): TranslatableMarkup {
    return $this->t('Failed jobs');
  }

  /**
   * Get all jobs that match a specific form.
   *
   * @todo Find a better way to get all jobids related to form, this is quite a load.
   *
   * @param $formId
   *   The form to match.
   *
   * @return array
   *   A list of view parameters.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getQueueJobIds($formId): array {
    $submissionIdsFromForm = $this->getSubmissionIdsFromForm($formId);
    $formJobs = [];
    $results = $this->failedJobsHelper->getAllJobs();

    foreach ($results as $result) {
      $submissionId = $this->failedJobsHelper->getSubmissionIdFromJob($result->job_id);

      if (in_array($submissionId, $submissionIdsFromForm)) {
        $formJobs[$result->job_id] = $result->job_id;
      }
    }

    return [implode(',', $formJobs)];
  }

  /**
   * Get Submission ids from form id.
   *
   * @param $formId
   *   The form id.
   *
   * @return array|int
   *   List of submissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getSubmissionIdsFromForm($formId) {
    $query = $this->entityTypeManager->getStorage('webform_submission')->getQuery();
    $query->condition('webform_id', $formId);

    return $query->execute();
  }
}