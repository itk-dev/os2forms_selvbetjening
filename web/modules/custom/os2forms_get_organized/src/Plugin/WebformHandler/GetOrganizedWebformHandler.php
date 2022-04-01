<?php

namespace Drupal\os2forms_get_organized\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\os2forms_get_organized\Exception\AttachmentElementNotFoundException;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get Organized Webform Handler.
 *
 * @WebformHandler(
 *   id = "get_organized",
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
  public function defaultConfiguration() {
    return [
      'message' => 'This is a custom message.',
      'debug' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->getLogger()->debug('This was the form: ' . print_r($this->getWebform()->getElementsDecoded(), TRUE));

    $form['case_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Case id (GetOrganized)'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['case_id'],
    ];

    $availableAttachmentElements = $this->getAvailableAttachmentElements($this->getWebform()->getElementsDecodedAndFlattened());

    $form['attachment_element'] = [
      '#type' => 'select',
      '#title' => $this->t('Attachment element'),
      '#options' => $availableAttachmentElements,
      '#default_value' => $this->configuration['attachment_element'],
      '#required' => TRUE,
      '#size' => 5,
    ];

    $form['development']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, every handler method invoked will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['debug'],
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
    $this->configuration['debug'] = (bool) $form_state->getValue('debug');
  }

  /**
   * {@inheritdoc}
   */
  public function alterElements(array &$elements, WebformInterface $webform) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function overrideSettings(array &$settings, WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
    if ($value = $form_state->getValue('element')) {
      $form_state->setErrorByName('element', $this->t('The element must be empty. You entered %value.', ['%value' => $value]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preCreate(array &$values) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postLoad(WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preDelete(WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $this->debug(__FUNCTION__, $update ? 'update' : 'insert');

    if (!$this->configuration['attachment_element']){
      throw new AttachmentElementNotFoundException();
    }

    $attachmentElement = $this->configuration['attachment_element'];
    $element = $webform_submission->getWebform()->getElement($attachmentElement, $webform_submission);
    $elementInfo = $this->elementInfo->createInstance('webform_entity_print_attachment');

    // Create temp file with attachment-element contents
    $fileContent = $elementInfo::getFileContent($element, $webform_submission);

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
   * {@inheritdoc}
   */
  public function preprocessConfirmation(array &$variables) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function createHandler() {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function updateHandler() {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteHandler() {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function createElement($key, array $element) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function updateElement($key, array $element, array $original_element) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteElement($key, array $element) {
    $this->debug(__FUNCTION__);
  }

  /**
   * Display the invoked plugin method to end user.
   *
   * @param string $method_name
   *   The invoked method name.
   * @param string $context1
   *   Additional parameter passed to the invoked method name.
   */
  protected function debug($method_name, $context1 = NULL) {
    if (!empty($this->configuration['debug'])) {
      $t_args = [
        '@id' => $this->getHandlerId(),
        '@class_name' => get_class($this),
        '@method_name' => $method_name,
        '@context1' => $context1,
      ];
      $this->messenger()->addWarning($this->t('Invoked @id: @class_name:@method_name @context1', $t_args), TRUE);
    }
  }

  /**
   * Get available attachment elements.
   */
  private function getAvailableAttachmentElements(array $elements): array
  {
    $availableElements = [];

    foreach ($elements as $key => $element) {

      if ('webform_entity_print_attachment:pdf' !== $element['#type']) {
        continue;
      }

      $availableElements[$key] = $element['#title'];
    }

    return $availableElements;
  }

}
