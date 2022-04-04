<?php

namespace Drupal\os2forms_get_organized\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\os2forms_get_organized\Exception\AttachmentElementNotFoundException;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get Organized Webform Handler.
 *
 * @WebformHandler(
 *   id = "os2forms_get_organized",
 *   label = @Translation("GetOrganized"),
 *   category = @Translation("Web services"),
 *   description = @Translation("Journalizes response in GetOrganized."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class GetOrganizedWebformHandler extends WebformHandlerBase {
  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * @var \Drupal\os2forms_get_organized\Helper\WebformHelper
   */
  protected $getOrganizedHelper;

  /**
   * Element info.
   *
   * @var \Drupal\Core\Render\ElementInfoManager
   */
  protected $elementInfo;

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
    $instance->getOrganizedHelper = $container->get('os2forms_get_organized.webform_helper');
    $instance->elementInfo = $container->get('plugin.manager.element_info');

    $instance->setConfiguration($configuration);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['case_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GetOrganized case ID'),
      '#description' => $this->t('The GetOrganized case that responses should be uploaded to.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['case_id'] ?? '',
    ];

    $form['attachment_element'] = [
      '#type' => 'select',
      '#title' => $this->t('Attachment element'),
      '#options' => $this->getAvailableAttachmentElements($this->getWebform()->getElementsDecodedAndFlattened()),
      '#default_value' => $this->configuration['attachment_element'] ?? '',
      '#description' => $this->t('Choose the element responsible for creating response attachments.'),
      '#required' => TRUE,
      '#size' => 5,
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['case_id'] = $form_state->getValue('case_id');
    $this->configuration['attachment_element'] = $form_state->getValue('attachment_element');
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {

    if (!$this->configuration['attachment_element']){
      throw new AttachmentElementNotFoundException();
    }

    // Get attachment element file contents
    $attachmentElement = $this->configuration['attachment_element'];
    $element = $webform_submission->getWebform()->getElement($attachmentElement, $webform_submission);
    $elementInfo = $this->elementInfo->createInstance('webform_entity_print_attachment');
    $fileContent = $elementInfo::getFileContent($element, $webform_submission);

    // Create temp file with attachment-element contents
    $webformLabel = $webform_submission->getWebform()->label();
    $tempFile = tempnam('/tmp', $webformLabel);
    file_put_contents($tempFile, $fileContent);

    $getOrganizedFileName = $webformLabel.'-'.$webform_submission->id().'.pdf';
    $getOrganizedCaseId = $this->configuration['case_id'];

    $this->getOrganizedHelper->journalize($tempFile, $getOrganizedCaseId, $getOrganizedFileName);

    // Remove temp file
    unlink($tempFile);
  }

  /**
   * Get available attachment elements.
   */
  private function getAvailableAttachmentElements(array $elements): array
  {
    $attachmentElements = array_filter($elements, function ($element) {
      return 'webform_entity_print_attachment:pdf' === $element['#type'];
    });

    return array_map(function ($element) {
      return $element['#title'];
    }, $attachmentElements);
  }

}
