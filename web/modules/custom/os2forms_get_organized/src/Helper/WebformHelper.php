<?php

namespace Drupal\os2forms_get_organized\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use ItkDev\GetOrganized\Client;
use ItkDev\GetOrganized\Service\Documents;

class WebformHelper
{
  private ?Client $client = null;
  private ConfigFactoryInterface $config;

  public function __construct(ConfigFactoryInterface $config)
  {
    $this->config = $config;
  }

  /**
   * Adds document to GetOrganized case.
   */
  public function journalize(string $filePath, string $getOrganizedCaseId, string $getOrganizedFileName)
  {
    if (null === $this->client) {
      $this->setupClient();
    }

    /** @var Documents $documentService */
    $documentService = $this->client->api('documents');
    $documentService->AddToDocumentLibrary($filePath, $getOrganizedCaseId, $getOrganizedFileName);
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
}
