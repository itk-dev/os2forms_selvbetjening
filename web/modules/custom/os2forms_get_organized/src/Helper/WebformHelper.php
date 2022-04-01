<?php

namespace Drupal\os2forms_get_organized\Helper;

use ItkDev\GetOrganized\Client;
use ItkDev\GetOrganized\Service\Documents;

class WebformHelper
{
  private ?Client $client = null;

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
    $username = \Drupal::config('get_organized')->get('username');
    $password = \Drupal::config('get_organized')->get('password');
    $baseUrl = \Drupal::config('get_organized')->get('base_url');

    $this->client = new Client($username, $password, $baseUrl);
  }
}
