<?php

namespace Drupal\os2forms_fbs_handler\Client;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\Client;

final class FBS {

  private string $sessionKey;

  public function __construct(
    private readonly Client $client,
    private readonly string $endpoint,
    private readonly string $agencyId,
    private readonly string $username,
    private readonly string $password
  ) {
  }

  /**
   * Login to FBS and obtain session key.
   *
   * @return bool
   *   TRUE on success else FALSE.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  public function login(): bool {
    $uri = '/external/v1/{agency_id}/authentication/login';
    $payload = [
      'username' => $this->username,
      'password' => $this->password
    ];

    $json = $this->request($uri, $payload);

    if (isset($json->sessionKey)) {
      $this->sessionKey = $json->sessionKey;

      return TRUE;
    }

    return FALSE;
  }

  public function doUserExists() {

  }

  public function createUser() {

  }

  /**
   * Send request to FSB.
   *
   * @param string $uri
   *   The uri/poth to send request to.
   * @param array $json
   *   The json to send to FBS.
   * @param string $method
   *   The type of request to send (Default: POST).
   *
   * @return mixed
   *   Json response from FBS.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  private function request(string $uri, array $json, string $method = RequestMethodInterface::METHOD_POST) {
    $url = rtrim($this->endpoint, '/\\');
    $url = $url . str_replace('{agency_id}', $this->agencyId, $uri);

    $options = [
      'headers' => [
        'Content-type' => 'application/json; charset=utf-8',
      ],
      'json' => $json
    ];
    $response = $this->client->request($method, $url, $options);

    return json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR);
  }
}

