<?php

namespace Drupal\os2forms_api_request_handler;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\os2forms_api_request_handler\Exception\ApiHandlerException;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/**
 * The POST helper.
 */
class PostHelper {

  /**
   * The EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private LanguageManagerInterface $languageManager;

  /**
   * The client.
   *
   * @var \GuzzleHttp\Client
   */
  private Client $client;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LanguageManagerInterface $languageManager, Client $client) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->client = $client;
  }

  /**
   * Post submission to API.
   */
  public function post(array $payload) {
    try {
      $submissionId = $payload['submission']['id'] ?? NULL;
      $handlerConfiguration = $payload['handler']['configuration'] ?? NULL;

      $submission = $this->getSubmission($submissionId);
      if (NULL === $submission) {
        throw new ApiHandlerException(sprintf('Cannot load submission %s', $submissionId));
      }

      $webform = $submission->getWebform();

      $links = [
        'sender' => $this->generateUrl('<front>'),
      ];
      try {
        $links['get_submission_url'] = $this->generateUrl(
          'rest.webform_rest_submission.GET',
          [
            'webform_id' => $webform->id(),
            'uuid' => $submission->uuid(),
          ]
        );
      }
      catch (\Exception $exception) {
      }

      $data = [
        'data' => [
          'webform' => [
            'id' => $webform->id(),
          ],
          'submission' => [
            'uuid' => $submission->uuid(),
          ],
        ],
        'links' => $links,
      ];

      if ($handlerConfiguration['post_full_submission'] ?? FALSE) {
        // @todo Add full submission data.
        // $data['submission'] += …
      }

      $apiUrl = $handlerConfiguration['api_url'] ?? NULL;
      $apiAuthorizationHeader = $handlerConfiguration['api_authorization_header'] ?? NULL;
      $this->client->request('POST', $apiUrl, [
        'headers' => [
          'Authorization' => $apiAuthorizationHeader,
        ],
        RequestOptions::JSON => $data,
      ]);
    }
    catch (\Exception $exception) {
      throw new ApiHandlerException($exception->getMessage(), $exception->getCode(), $exception);
    }
  }

  /**
   * Get webform submission by id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getSubmission(string $id): ?WebformSubmissionInterface {
    return $this->entityTypeManager
      ->getStorage('webform_submission')
      ->load($id);
  }

  /**
   * Generate url.
   */
  private function generateUrl(string $routeName, array $parameters = [], array $options = []): string {
    return Url::fromRoute(
      $routeName,
      $parameters,
      $options + [
        'absolute' => TRUE,
        'language' => $this->languageManager->getLanguage(LanguageInterface::LANGCODE_NOT_APPLICABLE),
      ]
    )->toString();
  }

}
