<?php

namespace Drupal\os2forms_rest_api\EventSubscriber;

use Drupal\file\Entity\File;
use Drupal\webform_rest\Event\WebformSubmissionDataEvent;
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
   * Submission data elements that should be updated.
   *
   * @var array
   */
  private $expands = [
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
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * Event handler.
   */
  public function onWebformSubmissionDataEvent(WebformSubmissionDataEvent $event) {
    // Expand query string should be csv.
    // @Example: file,name
    $expandQueryString = $this->requestStack->getCurrentRequest()->query->get('expand');

    if ($expandQueryString && is_string($expandQueryString)) {
      // Handle csv query string.
      if (strpos($expandQueryString, ',')) {

        $expandQueryArray = explode(',', $expandQueryString);

        foreach ($expandQueryArray as $value) {
          $this->handleExpandQueryValues($value, $event);
        }
      }
      else {
        $this->handleExpandQueryValues($expandQueryString, $event);
      }
    }
  }

  /**
   * Handles expand query values.
   */
  private function handleExpandQueryValues(string $value, WebformSubmissionDataEvent $event) {
    // Add cases as they become necessary.
    switch ($value) {
      case 'file':
        $this->fileHandler($event);
        break;
    }
  }

  /**
   * Handles manipulation of file data.
   */
  private function fileHandler(WebformSubmissionDataEvent $event) {

    // Get list of file fields.
    $elements = $event->getWebformSubmission()->getWebform()->getElementsDecodedAndFlattened();

    $fileFields = [];
    foreach ($elements as $key => $value) {
      if (in_array($value['#type'], $this->expands['file'])) {
        $fileFields[] = $key;
      }
    }

    $data = $event->getData();

    // Translate into actual file url.
    foreach ($fileFields as $fileField) {
      // Translate into actual file url.
      if (is_array($data[$fileField])) {
        $data[$fileField] = array_map([$this, 'normalizeFileIdToIdAndUrl'], $data[$fileField]);
      }
      else {
        $data[$fileField] = $this->normalizeFileIdToIdAndUrl($data[$fileField]);
      }
    }

    $event->setData($data);
  }

  /**
   * Updates file data.
   */
  private function normalizeFileIdToIdAndUrl(string $fileId): array {
    $file = File::load($fileId);
    $fileUrl = $file->createFileUrl(FALSE);

    return [
      'id' => $fileId,
      'url' => $fileUrl,
    ];
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
