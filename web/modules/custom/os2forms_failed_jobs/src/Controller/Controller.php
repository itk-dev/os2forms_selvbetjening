<?php

namespace Drupal\os2forms_failed_jobs\Controller;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\os2forms_failed_jobs\Helper\Helper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Render\RendererInterface;

/**
 * Controller for handling failed jobs.
 */
class Controller extends ControllerBase {

  /**
   * Failed jobs helper.
   *
   * @var \Drupal\os2forms_failed_jobs\Helper\Helper
   */
  protected Helper $failedJobsHelper;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Request stack.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * Failed jobs constructor.
   */
  public function __construct(EntityTypeManager $entityTypeManager, Helper $failedJobsHelper, RequestStack $requestStack, RendererInterface $renderer) {
    $this->entityTypeManager = $entityTypeManager;
    $this->failedJobsHelper = $failedJobsHelper;
    $this->requestStack = $requestStack;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): Controller {
    return new static(
      $container->get('entity_type.manager'),
      $container->get(Helper::class),
      $container->get('request_stack'),
      $container->get('renderer')
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
   * @throws \Exception
   */
  public function render(): array {
    $view = Views::getView('os2forms_failed_jobs');
    $view->setDisplay('block_1');
    // Add custom argument that the views_ui cannot provide.
    $formId = $this->requestStack->getCurrentRequest()->get('webform')->id();
    $view->setArguments([implode(',', $this->getQueueJobIds($formId))]);
    $view->execute();

    return $view->render() ?? ['#markup' => $this->t('No failed jobs')];
  }

  /**
   * Add title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A translatable string.
   */
  public function title(): TranslatableMarkup {
    return $this->t('Failed jobs');
  }

  /**
   * Get all jobs that match a specific form.
   *
   * @todo Find a better way to get all jobids related to form, this is quite a load.
   *
   * @param string $formId
   *   The form to match.
   *
   * @return array
   *   A list of view parameters.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getQueueJobIds(string $formId): array {
    $submissionIdsFromForm = $this->getSubmissionsFromForm($formId);
    $formJobs = [];
    $results = $this->failedJobsHelper->getAllJobs();

    foreach ($results as $result) {
      $submissionId = $this->failedJobsHelper->getSubmissionIdFromJob($result->job_id);

      if (in_array($submissionId, $submissionIdsFromForm)) {
        $formJobs[$result->job_id] = $result->job_id;
      }
    }

    return $formJobs;
  }

  /**
   * Get Submissions from form id.
   *
   * @param string $formId
   *   The form id.
   *
   * @return array
   *   List of submissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getSubmissionsFromForm(string $formId): array {
    return $this->entityTypeManager
      ->getStorage('webform_submission')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('webform_id', $formId)
      ->execute();
  }

}
