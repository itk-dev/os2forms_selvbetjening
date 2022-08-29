<?php

namespace Drupal\os2forms_rest_api\EventSubscriber;

use Drupal\file\Entity\File;
use Drupal\webform_rest\Event\WebformSubmissionDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class WebformSubmissionEventSubscriber implements EventSubscriberInterface
{

  /** @var RequestStack */
  protected $requestStack;

  private $expands = [
    'file' => ['webform_image_file', 'webform_document_file', 'webform_video_file', 'webform_audio_file', 'webform_managed_file'],
  ];

  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  public function onWebformSubmissionDataEvent(WebformSubmissionDataEvent $event)
  {
    // Expand query string should be csv of whichever data should be expanded or manipulated.
    // @Example: file,name
    $expandQueryString = $this->requestStack->getCurrentRequest()->query->get('expand');

    if ($expandQueryString && is_string($expandQueryString)) {
      // Handle csv query string
      if (strpos($expandQueryString, ',')) {

        $expandQueryArray = explode(',', $expandQueryString);

        foreach ($expandQueryArray as $value) {
          $this->handleExpandQueryValues($value, $event);
        }
      } else {
        $this->handleExpandQueryValues($expandQueryString, $event);
      }
    }
  }

  private function handleExpandQueryValues(string $value, WebformSubmissionDataEvent $event) {
    // Add cases as they become necessary.
    switch ($value) {
      case 'file':
        $this->fileHandler($event);
        break;
    }
  }

  private function fileHandler(WebformSubmissionDataEvent $event)
  {

    // Get list of file fields
    $elements = $event->getWebformSubmission()->getWebform()->getElementsDecodedAndFlattened();

    $fileFields = [];
    foreach ($elements as $key => $value) {
      if (in_array($value['#type'], $this->expands['file'])) {
        $fileFields[] = $key;
      }
    }

    $data = $event->getData();

    // Translate into actual file url
    foreach ($fileFields as $fileField) {
      // Translate into actual file url
      if (is_array($data[$fileField])) {
        $data[$fileField] = array_map([$this, 'normalizeFileIdToIdAndUrl'], $data[$fileField]);
      } else {
        $data[$fileField] = $this->normalizeFileIdToIdAndUrl($data[$fileField]);
      }
    }

    $event->setData($data);
  }

  private function normalizeFileIdToIdAndUrl(string $fileId): array
  {
    $file = File::load($fileId);
    $fileUrl = $file->createFileUrl(false);

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
