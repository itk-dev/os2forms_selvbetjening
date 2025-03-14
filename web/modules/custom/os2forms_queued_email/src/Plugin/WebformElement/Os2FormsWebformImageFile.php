<?php

namespace Drupal\os2forms_queued_email\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformImageFile;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Extends 'webform_image_file' element.
 *
 * @WebformElement(
 *   id = "os2forms_webform_image_file",
 *   label = @Translation("Image file"),
 *   description = @Translation("Provides a form element for uploading and saving an image file."),
 *   category = @Translation("File upload elements"),
 *   states_wrapper = TRUE,
 *   dependencies = {
 *     "file",
 *   }
 * )
 */
class Os2FormsWebformImageFile extends WebformImageFile {

  /**
   * {@inheritdoc}
   *
   * OS2Forms webform image file element.
   *
   * Copied from WebformImageFile::getEmailAttachments.
   * Only change is that filecontent is not passed on.
   *
   * @see parent::getEmailAttachments()
   */
  public function getEmailAttachments(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $attachments = [];

    /** @var \Drupal\image\ImageStyleInterface $image_style */
    $image_style = NULL;
    $attachment_image_style = $this->getElementProperty($element, 'attachment_image_style');
    if ($attachment_image_style && $this->moduleHandler->moduleExists('image')) {
      $image_style = $this->entityTypeManager
        ->getStorage('image_style')
        ->load($attachment_image_style);
    }

    $files = $this->getTargetEntities($element, $webform_submission, $options) ?: [];
    foreach ($files as $file) {
      if ($image_style) {
        $file_uri = $image_style->buildUri($file->getFileUri());
        if (!file_exists($file_uri)) {
          $image_style->createDerivative($file->getFileUri(), $file_uri);
        }
        $file_url = $image_style->buildUrl($file->getFileUri());
      }
      else {
        $file_uri = $file->getFileUri();
        $file_url = $file->createFileUrl(FALSE);
      }
      $attachments[] = [
        'filecontent' => '',
        'filename' => $file->getFilename(),
        'filemime' => $file->getMimeType(),
        // File URIs that are not supported return FALSE, when this happens
        // still use the file's URI as the file's path.
        'filepath' => $this->fileSystem->realpath($file_uri) ?: $file_uri,
        // URL is used when debugging or resending messages.
        // @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::buildAttachments
        '_fileurl' => $file_url,
      ];
    }
    return $attachments;
  }

}
