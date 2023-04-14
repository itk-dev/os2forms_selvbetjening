<?php

namespace Drupal\os2forms_fbs_handler\Client;

use Drupal\os2forms_fbs_handler\Client\Model\Guardian;
use Drupal\os2forms_fbs_handler\Client\Model\Patron;
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
      'password' => $this->password,
    ];

    $json = $this->request($uri, $payload);
    if (isset($json->sessionKey)) {
      $this->sessionKey = $json->sessionKey;

      return TRUE;
    }

    return FALSE;
  }

  public function isLoggedIn(): bool {
    return isset($this->sessionKey);
  }

  /**
   * Check if user exists.
   *
   * @param $cpr
   *   The users personal security number.
   *
   * @return Patron|null
   *   NULL if not else the Patron.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  public function doUserExists(string $cpr): ?Patron {
    // Check if session have been created with FBS and if not create it.
    if (!$this->isLoggedIn()) {
      $this->login();
    }

    // Try pre-authenticate the user/parent
    $json = $this->request('/external/{agency_id}/patrons/preauthenticated/v9', $cpr);
    if ($json->authenticateStatus === 'VALID') {
      return new Patron(
        $json->patron->patronId,
        (bool) $json->patron->receiveSms,
        (bool) $json->patron->receivePostalMail,
        $json->patron->notificationProtocols,
        $json->patron->phoneNumber,
        $json->patron->onHold,
        $json->patron->preferredLanguage,
        $json->patron->guardianVisibility,
        $json->patron->emailAddress,
        (bool) $json->patron->receiveEmail,
        $json->patron->preferredPickupBranch
      );
    }

    return NULL;
  }

  public function createPatronWithGuardian(Patron $patron, Guardian $guardian) {
    $uri = '/external/{agency_id}/patrons/withGuardian/v1';
    $payload = [
      'cprNumber' => $patron->cpr,
      'pincode' => $patron->pincode,
      'preferredPickupBranch' => $patron->preferredPickupBranch,
      'name' => 'Unknown Name',
      'email' => $patron->emailAddress,
      'guardian' => $guardian->toArray(),
    ];

    return $this->request($uri, $payload, );
  }

  /**
   * Update patron information.
   *
   * @param Patron $patron
   *   The patron to update.
   *
   * @return bool
   *   TRUE if success else FALSE.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  public function updatePatron(Patron $patron): bool {
    $uri = '/external/{agency_id}/patrons/'.$patron->patronId.'/v6';
    $payload = [
      'patronid' => $patron->patronId,
      'patron' => $patron->toArray(),
      'pincodeChange' => [
        'pincode' => $patron->pincode,
        'libraryCardNumber' => $patron->cpr,
      ],
    ];

    $json = $this->request($uri, $payload, RequestMethodInterface::METHOD_PUT);

    return $json->authenticateStatus === 'VALID';
  }

  /**
   * Create guardian for patron.
   *
   * @param Patron $patron
   *   Patron to create guardian for.
   * @param Guardian $guardian
   *   The guardian to create.
   *
   * @return int
   *   Guardian identifier.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  public function createGuardian(Patron $patron, Guardian $guardian): int {
    $uri = '/external/{agency_id}/patrons/withGuardian/v1';
    $payload = [
      'patronId' => $patron->patronId,
      'guardian' => $guardian->toArray(),
    ];

    return $this->request($uri, $payload, RequestMethodInterface::METHOD_PUT);
  }

  /**
   * Send request to FSB.
   *
   * @param string $uri
   *   The uri/path to send request to.
   * @param array|string $data
   *   The json or string to send to FBS.
   * @param string $method
   *   The type of request to send (Default: POST).
   *
   * @return mixed
   *   Json response from FBS.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  private function request(string $uri, array|string $data, string $method = RequestMethodInterface::METHOD_POST): mixed {
    $url = rtrim($this->endpoint, '/\\');
    $url = $url . str_replace('{agency_id}', $this->agencyId, $uri);

    $options = [
      'headers' => [
        'Content-type' => 'application/json; charset=utf-8',
      ],
    ];

    // The API designer at FBS don't always use JSON. So in some cases only a
    // string should be sent.
    if (is_array($data)) {
      $options['json'] = $data;
    }
    else {
      $options['body'] = $data;
    }

    // If already logged in lets add the session key to the request headers.
    if ($this->isLoggedIn()) {
      $options['headers']['X-Session'] = $this->sessionKey;
    }

    $response = $this->client->request($method, $url, $options);

    return json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR);
  }
}

