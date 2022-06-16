<?php

namespace Drupal\os2forms_get_organized\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform_entity_print_attachment\Element\WebformEntityPrintAttachment;
use ItkDev\GetOrganized\Client;

/**
 * Helper for archiving documents in GetOrganized.
 */
class ArchiveHelper {
  /**
   * The GetOrganized Client.
   *
   * @var \ItkDev\GetOrganized\Client|null
   */
  private ?Client $client = NULL;

  /**
   * The ConfigFactoryInterface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $config;

  /**
   * The EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs an ArchiveHelper object.
   */
  public function __construct(ConfigFactoryInterface $config, EntityTypeManagerInterface $entityTypeManager) {
    $this->config = $config;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Adds document to GetOrganized case.
   */
  public function archive(string $submissionId, array $handlerConfiguration) {
    /** @var \Drupal\webform\Entity\WebformSubmission $submission */
    $submission = $this->getSubmission($submissionId);

    $getOrganizedCaseId = $handlerConfiguration['case_id'];
    $webformAttachmentElementId = $handlerConfiguration['attachment_element'];

    $element = $submission->getWebform()->getElement($webformAttachmentElementId, $submission);
    $fileContent = WebformEntityPrintAttachment::getFileContent($element, $submission);

    // Create temp file with attachment-element contents.
    $webformLabel = $submission->getWebform()->label();
    $tempFile = tempnam('/tmp', $webformLabel);
    file_put_contents($tempFile, $fileContent);

    $getOrganizedFileName = $webformLabel . '-' . $submission->serial() . '.pdf';

    if (NULL === $this->client) {
      $this->setupClient();
    }

    /** @var \ItkDev\GetOrganized\Service\Documents $documentService */
    $documentService = $this->client->api('documents');
    $result = $documentService->AddToDocumentLibrary($tempFile, $getOrganizedCaseId, $getOrganizedFileName);

    // Remove temp file.
    unlink($tempFile);

    // Handle finalization ("journalisering").
    $shouldBeFinalized = $handlerConfiguration['should_be_finalized'];

    if ($shouldBeFinalized) {
      if (isset($result['DocId'])) {
        $documentService->Finalize($result['DocId']);
      }
    }
  }

  /**
   * Sets up Client.
   */
  private function setupClient() {
    $config = $this->config->get('os2forms_get_organized');
    $username = $config->get('username');
    $password = $config->get('password');
    $baseUrl = $config->get('base_url');

    $this->client = new Client($username, $password, $baseUrl);
  }

  /**
   * Gets WebformSubmission from id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getSubmission(string $submissionId) {
    $storage = $this->entityTypeManager->getStorage('webform_submission');
    return $storage->load($submissionId);
  }

}
