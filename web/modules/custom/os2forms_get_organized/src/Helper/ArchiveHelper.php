<?php

namespace Drupal\os2forms_get_organized\Helper;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform\Entity\WebformSubmission;
use ItkDev\GetOrganized\Client;
use ItkDev\GetOrganized\Service\Documents;

class ArchiveHelper
{
  private ?Client $client = null;
  private ConfigFactoryInterface $config;
  private PluginManagerInterface $elementInfo;
  private EntityTypeManagerInterface $entityTypeManager;

  public function __construct(ConfigFactoryInterface $config, EntityTypeManagerInterface $entityTypeManager, PluginManagerInterface $elementInfo)
  {
    $this->config = $config;
    $this->entityTypeManager = $entityTypeManager;
    $this->elementInfo = $elementInfo;
  }

  /**
   * Adds document to GetOrganized case.
   */
  public function archive(string $submissionId, array $handlerConfiguration)
  {
    /** @var WebformSubmission $submission */
    $submission = $this->getSubmission($submissionId);

    $getOrganizedCaseId = $handlerConfiguration['case_id'];
    $webformAttachmentElementId = $handlerConfiguration['attachment_element'];

    $element = $submission->getWebform()->getElement($webformAttachmentElementId, $submission);
    $elementInfo = $this->elementInfo->createInstance('webform_entity_print_attachment');
    $fileContent = $elementInfo::getFileContent($element, $submission);

    // Create temp file with attachment-element contents
    $webformLabel = $submission->getWebform()->label();
    $tempFile = tempnam('/tmp', $webformLabel);
    file_put_contents($tempFile, $fileContent);

    $getOrganizedFileName = $webformLabel.'-'.$submission->serial().'.pdf';

    if (null === $this->client) {
      $this->setupClient();
    }

    /** @var Documents $documentService */
    $documentService = $this->client->api('documents');
    $documentService->AddToDocumentLibrary($tempFile, $getOrganizedCaseId, $getOrganizedFileName);

    // Remove temp file
    unlink($tempFile);
  }

  /**
   * Sets up Client.
   */
  private function setupClient()
  {
    $config = $this->config->get('os2forms_get_organized');
    $username = $config->get('username');
    $password = $config->get('password');
    $baseUrl = $config->get('base_url');

    $this->client = new Client($username, $password, $baseUrl);
  }

  /**
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  private function getSubmission(string $submissionId) {
    $storage = $this->entityTypeManager->getStorage('webform_submission');
    return $storage->load($submissionId);
  }
}
