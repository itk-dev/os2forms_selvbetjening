<?php

namespace Drupal\os2forms_get_organized\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\os2forms_get_organized\Exception\GetOrganizedQueueException;
use ItkDev\GetOrganized\Client;
use ItkDev\GetOrganized\Exception\GetOrganizedClientException;
use ItkDev\GetOrganized\Exception\InvalidFilePathException;
use ItkDev\GetOrganized\Exception\InvalidServiceNameException;
use ItkDev\GetOrganized\Service\Documents;

class ArchiveHelper
{
  private ?Client $client = null;
  private ConfigFactoryInterface $config;

  const FILE_PATH_OPTION = 'filePath';
  const GET_ORGANIZED_CASE_ID = 'getOrganizedCaseId';
  const GET_ORGANIZED_FILE_NAME = 'getOrganizedFileName';

  const PAYLOAD_REQUIRED_OPTIONS = [
    self::FILE_PATH_OPTION,
    self::GET_ORGANIZED_CASE_ID,
    self::GET_ORGANIZED_FILE_NAME,
  ];

  public function __construct(ConfigFactoryInterface $config)
  {
    $this->config = $config;
  }

  /**
   * Adds document to GetOrganized case.
   * @throws GetOrganizedQueueException
   */
  public function archive(array $payload)
  {
    foreach (self::PAYLOAD_REQUIRED_OPTIONS as $option) {
      if (!isset($payload[$option])) {
        $message = sprintf('Required payload option %s missing.', $option);
        throw new GetOrganizedQueueException($message);
      }
    }

    if (null === $this->client) {
      $this->setupClient();
    }

    try {
      /** @var Documents $documentService */
      $documentService = $this->client->api('documents');
      $documentService->AddToDocumentLibrary($payload[self::FILE_PATH_OPTION], $payload[self::GET_ORGANIZED_CASE_ID], $payload[self::GET_ORGANIZED_FILE_NAME]);

      // Remove temp file
      unlink($payload[self::FILE_PATH_OPTION]);
    } catch (InvalidServiceNameException|InvalidFilePathException|GetOrganizedClientException $e) {
      throw new GetOrganizedQueueException($e->getMessage());
    }
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
