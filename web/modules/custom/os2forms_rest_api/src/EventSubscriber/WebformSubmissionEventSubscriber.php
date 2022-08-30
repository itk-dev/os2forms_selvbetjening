<?php

namespace Drupal\os2forms_rest_api\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform_rest\Event\WebformSubmissionDataEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * WebformSubmissionEventSubscriber, for updating Webform Submission GET data.
 */
class WebformSubmissionEventSubscriber implements EventSubscriberInterface {

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * Map from entity type to webform element types.
   */
  private const LINKED_ELEMENT_TYPES = [
    'file' => [
      'webform_image_file',
      'webform_document_file',
      'webform_video_file',
      'webform_audio_file',
      'webform_managed_file',
    ],
  ];

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RequestStack $requestStack, LoggerInterface $logger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->requestStack = $requestStack;
    $this->logger = $logger;
  }

  /**
   * Event handler.
   */
  public function onWebformSubmissionDataEvent(WebformSubmissionDataEvent $event) {

    $linkedData = $this->buildLinked($event->getWebformSubmission()->getWebform(), $event->getData());

    if (!empty($linkedData)) {
      $event->setData($event->getData() + ['linked' => $linkedData]);
    }
  }

  /**
   * Builds linked entity data.
   *
   * @see https://support.deskpro.com/en/guides/developers/deskpro-api/basics/sideloading
   */
  private function buildLinked(WebformInterface $webform, array $data) {

    $linked = [];
    $elements = $webform->getElementsDecodedAndFlattened();

    foreach ($elements as $name => $element) {
      if (!isset($data[$name])) {
        continue;
      }

      $linkedEntityType = NULL;
      if (isset($element['#target_type'])) {
        $linkedEntityType = $element['#target_type'];
      }
      else {
        foreach (self::LINKED_ELEMENT_TYPES as $entityType => $elementTypes) {
          if (in_array($element['#type'], $elementTypes, TRUE)) {
            $linkedEntityType = $entityType;
            break;
          }
        }
      }

      if (NULL !== $linkedEntityType) {
        // $data[$name] is either a string id i.e. '127',
        // or an array of string ids i.e. ['127', '128'].
        // Casting to array allow us to handle both cases the same way.
        $values = (array) $data[$name];
        $entities = $this->entityTypeManager->getStorage($linkedEntityType)->loadMultiple($values);

        foreach ($entities as $value => $entity) {
          $link = [];
          if ($entity instanceof FileInterface) {
            $link = [
              'id' => $entity->id(),
              'url' => $entity->createFileUrl(FALSE),
              'mime_type' => $entity->getMimeType(),
              'size' => $entity->getSize(),
            ];
          }
          else {
            $this->logger->warning(sprintf('Unhandled linked entity type %s', $linkedEntityType));
          }
          if (!empty($link)) {
            $linked[$name][$value] = $link;
          }
        }
      }
    }
    return $linked;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      WebformSubmissionDataEvent::class => ['onWebformSubmissionDataEvent'],
    ];
  }

}
