<?php

namespace Drupal\os2forms_maestro_webform\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\os2forms_digital_post\Helper\WebformHelperSF1601;
use Drupal\webform\Plugin\WebformHandlerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Maestro notification handler.
 *
 * @WebformHandler(
 *   id = "os2forms_maestro_webform_notification",
 *   label = @Translation("Maestro notification"),
 *   category = @Translation("Web services"),
 *   description = @Translation("Sends Meastro notfications to users."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
final class NotificationHandler extends WebformHandlerBase {
  public const NOTIFICATION = 'notification';

  public const TYPE = 'type';
  public const SENDER_LABEL = 'sender_label';
  public const NOTIFICATION_SUBJECT = 'notification_subject';
  public const NOTIFICATION_CONTENT = 'notification_content';
  public const RECIPIENT_ELEMENT = 'recipient_element';

  /**
   * Maximum length of sender label.
   */
  private const SENDER_LABEL_MAX_LENGTH = 64;

  /**
   * Maximum length of header label.
   */
  private const NOTIFICATION_SUBJECT_MAX_LENGTH = 128;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The webform helper.
   *
   * @var \Drupal\os2forms_digital_post\WebformHelper
   */
  protected $webformHelper;

  /**
   * The template manager.
   *
   * @var \Drupal\os2forms_digital_post\Manager\TemplateManager
   */
  protected $templateManager;

  /**
   * The print service consumer.
   *
   * @var \Drupal\os2forms_digital_post\Consumer\PrintServiceConsumer
   */
  protected $printServiceConsumer;

  /**
   * The cpr service.
   *
   * @var \Drupal\os2forms_cpr_lookup\Service\CprServiceInterface
   */
  protected $cprService;

  /**
   * The webform helper.
   *
   * @var \Drupal\os2forms_digital_post\Helper\WebformHelperSF1601
   */
  protected WebformHelperSF1601 $helper;

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
    $instance->webformHelper = $container->get('os2forms_digital_post.webform_helper');
    $instance->templateManager = $container->get('os2forms_digital_post.template_manager');
    $instance->printServiceConsumer = $container->get('os2forms_digital_post.print_service_consumer');
    $instance->cprService = $container->get('os2forms_cpr_lookup.service');
    $instance->helper = $container->get(WebformHelperSF1601::class);

    $instance->setConfiguration($configuration);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#markup' => $this->t('<strong>Note</strong> This a not a real webform handler run when a submission is created, but run when Maestro sends out a notification. The notification will be sent to the person identified by the value of the %element element.', [
        '%element' => $this->configuration[self::NOTIFICATION][self::RECIPIENT_ELEMENT] ?? NULL,
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $form[self::NOTIFICATION] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Notification'),
    ];

    $availableElements = $this->getRecipientElements();
    $form[self::NOTIFICATION][static::RECIPIENT_ELEMENT] = [
      '#type' => 'select',
      '#title' => $this->t('Element that contains the recipient identifier (email, CPR or CVR) of the notification'),
      '#required' => TRUE,
      '#default_value' => $this->configuration[self::NOTIFICATION][self::RECIPIENT_ELEMENT] ?? NULL,
      '#options' => $availableElements,
    ];

    $form[self::NOTIFICATION][self::SENDER_LABEL] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender label'),
      '#required' => TRUE,
      '#default_value' => $this->configuration[self::NOTIFICATION][self::SENDER_LABEL] ?? NULL,
      '#maxlength' => self::SENDER_LABEL_MAX_LENGTH,
    ];

    $form[self::NOTIFICATION][self::NOTIFICATION_SUBJECT] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification subject'),
      '#required' => TRUE,
      '#default_value' => $this->configuration[self::NOTIFICATION][self::NOTIFICATION_SUBJECT] ?? NULL,
      '#maxlength' => self::NOTIFICATION_SUBJECT_MAX_LENGTH,
    ];

    $form[self::NOTIFICATION][self::NOTIFICATION_CONTENT] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notification text'),
      '#required' => TRUE,
      '#default_value' => $this->configuration[self::NOTIFICATION][self::NOTIFICATION_CONTENT] ?? NULL,
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * Get recipient elements.
   */
  private function getRecipientElements(): array {
    $elements = $this->getWebform()->getElementsDecodedAndFlattened();

    $elementTypes = [
      'email',
      'textfield',
      'cpr_element',
      'cpr_value_element',
      'cvr_element',
      'cvr_value_element',
    ];
    $elements = array_filter(
      $elements,
      static function (array $element) use ($elementTypes) {
        return in_array($element['#type'], $elementTypes, TRUE);
      }
    );

    return array_map(static function (array $element) {
      return $element['#title'];
    }, $elements);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $formState) {
    parent::submitConfigurationForm($form, $formState);

    $this->configuration[self::NOTIFICATION] = $formState->getValue(self::NOTIFICATION);
  }

}
