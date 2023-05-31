<?php

namespace Drupal\os2forms_forloeb\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Maestro notification handler.
 *
 * @WebformHandler(
 *   id = "os2forms_forloeb_notification",
 *   label = @Translation("OS2Forms forløb notification"),
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
  public const NOTIFICATION_ACTION_LABEL = 'notification_action_label';
  public const RECIPIENT_ELEMENT = 'recipient_element';

  private const TOKEN_MAESTRO_TASK_URL = '[maestro:task-url]';

  /**
   * Maximum length of sender label.
   */
  private const SENDER_LABEL_MAX_LENGTH = 64;

  /**
   * Maximum length of header label.
   */
  private const NOTIFICATION_SUBJECT_MAX_LENGTH = 128;

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

    $instance->setConfiguration($configuration);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#markup' => $this->t('Sends notification when triggered by Maestro. The notification will be sent to the person identified by the value of the %element element.', [
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

    $content = $this->configuration[self::NOTIFICATION][self::NOTIFICATION_CONTENT] ?? NULL;
    if (isset($content['value'])) {
      $content = $content['value'];
    }
    $form[self::NOTIFICATION][self::NOTIFICATION_CONTENT] = [
      '#type' => 'text_format',
      '#format' => 'restricted_html',
      '#title' => $this->t('Notification text'),
      '#required' => TRUE,
      '#default_value' => $content,
      '#description' => $this->t('The actual notification content. Must contain the <code>@token_maestro_task_url</code> token which is the URL to the Maestro task.', [
        '@token_maestro_task_url' => self::TOKEN_MAESTRO_TASK_URL,
      ]),
    ];

    $form[self::NOTIFICATION][self::NOTIFICATION_ACTION_LABEL] = [
      '#type' => 'textfield',
      '#title' => $this->t('Action label'),
      '#default_value' => $this->configuration[self::NOTIFICATION][self::NOTIFICATION_ACTION_LABEL] ?? NULL,
      '#description' => $this->t('Label of the action show in digital post'),
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $formState) {
    parent::validateConfigurationForm($form, $formState);

    $key = [self::NOTIFICATION, self::NOTIFICATION_CONTENT];
    $content = $formState->getValue($key);
    if (isset($content['value'])) {
      $content = $content['value'];
    }
    if (!str_contains($content, self::TOKEN_MAESTRO_TASK_URL)) {
      $formState->setErrorByName(
        implode('][', [self::NOTIFICATION, self::NOTIFICATION_CONTENT]),
        $this->t('The notification content must contain the <code>@token_maestro_task_url</code> token', [
          '@token_maestro_task_url' => self::TOKEN_MAESTRO_TASK_URL,
        ])
      );
    }
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
      'os2forms_person_lookup',
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
