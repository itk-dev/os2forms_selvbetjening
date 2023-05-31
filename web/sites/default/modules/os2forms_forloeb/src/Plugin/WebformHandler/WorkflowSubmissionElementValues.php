<?php

namespace Drupal\os2forms_forloeb\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\os2forms_forloeb\MaestroHelper;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Maestro notification handler.
 *
 * @WebformHandler(
 *   id = "os2forms_forloeb_workflow_submission_element",
 *   label = @Translation("Maestro workflow submission element"),
 *   category = @Translation("Web services"),
 *   description = @Translation("Prefills form elements with values from workflow submissions."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
final class WorkflowSubmissionElementValues extends WebformHandlerBase {
  public const SPEC = 'spec';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    $instance->loggerFactory = $container->get('logger.factory');
    $instance->configFactory = $container->get('config.factory');
    $instance->renderer = $container->get('renderer');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->conditionsValidator = $container->get('webform_submission.conditions_validator');
    $instance->tokenManager = $container->get('webform.token_manager');

    $instance->setConfiguration($configuration);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function alterElementsHest(array &$elements, WebformInterface $webform) {
    $spec = $this->getSpec();

    $submissionValues = [];
    if ($queueID = \Drupal::request()->query->get('queueid')) {
      if ($processID = (MaestroEngine::getProcessIdFromQueueId($queueID) ?: NULL)) {
        $entityIdentifiers = MaestroHelper::getWebformSubmissionIdentifiersForProcess($processID);
        foreach ($entityIdentifiers as $entityIdentifier) {
          /** @var \Drupal\webform\WebformSubmissionInterface $submission */
          $submission = $this->submissionStorage->load($entityIdentifier['entity_id']);
          $webform = $submission->getWebform();
          $submissionValues[$webform->id()] = $submission->getData();
        }
      }
    }

    foreach ($spec as $key => $info) {
      if (isset($elements[$key])) {
        if (isset($info['form'], $info['element'])
          && isset($submissionValues[$info['form']])
          && isset($submissionValues[$info['form']][$info['element']])) {
          $elements[$key]['#default_value'] = $submissionValues[$info['form']][$info['element']];
        }
      }
    }
  }

  /**
   * Get spec.
   */
  private function getSpec(): array {
    $spec = [];
    try {
      $spec = Yaml::decode($this->configuration[self::SPEC]);
    }
    catch (\Throwable) {
    }

    return is_array($spec) ? $spec : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#markup' => $this->t('Fill elements with flow values: <pre>%spec</pre>', ['%spec' => Yaml::encode($this->getSpec())]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $form[self::SPEC] = [
      '#type' => 'textarea',
      '#title' => $this->t('Spec'),
      '#description' => $this->t('Default value on elements will override this'),
      '#required' => TRUE,
      '#default_value' => $this->configuration[self::SPEC] ?? NULL,
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $formState) {
    parent::submitConfigurationForm($form, $formState);

    $this->configuration[self::SPEC] = $formState->getValue(self::SPEC);
  }

}
