<?php

namespace Drupal\os2forms_fbs_handler\Client;

use Drupal\os2forms_fbs_handler\Client\Model\Guardian;
use Drupal\os2forms_fbs_handler\Client\Model\Patron;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\Client;

/**
 * Minimalistic client to create user with guardians at FBS.
 */
final class FBS {

  /**
   * FBS session key.
   *
   * @var string
   */
  private string $sessionKey;

  /**
   * Default constructor.
   */
  public function __construct(
    private readonly Client $client,
    private readonly string $endpoint,
    private readonly string $agencyId,
    private readonly string $username,
    private readonly string $password,
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

  /**
   * Check is user is logged in.
   *
   * @return bool
   *   TRUE if logged in else FALSE.
   */
  public function isLoggedIn(): bool {
    return isset($this->sessionKey);
  }

  /**
   * Check if user exists.
   *
   * @param string $cpr
   *   The users personal security number.
   *
   * @return string|null
   *   NULL if not else the PatronId.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  public function authenticatePatron(string $cpr): ?string {
    // Check if session have been created with FBS and if not create it.
    if (!$this->isLoggedIn()) {
      $this->login();
    }

    // Authenticate the patron.
    $json = $this->request('/external/{agency_id}/patrons/preauthenticated/v10', $cpr);
    if ($json->authenticateStatus === 'VALID') {
      return $json->patronId;
    }

    return NULL;
  }

  /**
   * Create new patron with guardian attached.
   *
   * @param \Drupal\os2forms_fbs_handler\Client\Model\Patron $patron
   *   The patron to create.
   * @param \Drupal\os2forms_fbs_handler\Client\Model\Guardian $guardian
   *   The guardian to attach to the parton.
   *
   * @return mixed
   *   JSON response from FBS.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  public function createPatronWithGuardian(Patron $patron, Guardian $guardian) {
    $uri = '/external/{agency_id}/patrons/withGuardian/v4';
    $payload = [
      'personId' => $patron->personId,
      'pincode' => $patron->pincode,
      'preferredPickupBranch' => $patron->preferredPickupBranch,
      'name' => 'Unknown Name',
      'emailAddresses' => $patron->emailAddresses,
      'guardian' => $guardian->toArray(),
    ];

    return $this->request($uri, $payload);
  }

  /**
   * Update patron information.
   *
   * @param string $patronId
   *   The patron to update.
   *
   * @return \Drupal\os2forms_fbs_handler\Client\Model\Patron
   *   Patron object
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  public function getPatron(string $patronId): ?Patron {
    $uri = '/external/{agency_id}/patrons/' . $patronId . '/v4';

    $json = $this->request($uri, [], RequestMethodInterface::METHOD_GET);

    if ($json->authenticateStatus === "VALID") {
      return new Patron(
        $json->patron->patronId,
        (bool) $json->patron->receiveSms,
        (bool) $json->patron->receivePostalMail,
        $json->patron->notificationProtocols,
        $json->patron->phoneNumber,
        is_null($json->patron->onHold) ? $json->patron->onHold : (array) $json->patron->onHold,
        $json->patron->preferredLanguage,
        (bool) $json->patron->guardianVisibility,
        $json->patron->defaultInterestPeriod,
        (bool) $json->patron->resident,
        [
          [
            'emailAddress' => $json->patron->emailAddress,
            'receiveNotification' => $json->patron->receiveEmail,
          ],
        ],
        (bool) $json->patron->receiveEmail,
        $json->patron->preferredPickupBranch
      );
    }
    return NULL;
  }

  /**
   * Update patron information.
   *
   * @param \Drupal\os2forms_fbs_handler\Client\Model\Patron $patron
   *   The patron to update.
   *
   * @return bool
   *   TRUE if success else FALSE.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  public function updatePatron(Patron $patron): bool {
    $uri = '/external/{agency_id}/patrons/' . $patron->patronId . '/v8';
    $payload = [
      'patron' => [
        'preferredPickupBranch' => $patron->preferredPickupBranch,
        'emailAddresses' => $patron->emailAddresses,
        'guardianVisibility' => $patron->guardianVisibility,
        'receivePostalMail' => $patron->receiveEmail,
        'phoneNumbers' => [
          [
            'receiveNotification' => TRUE,
            'phoneNumber' => $patron->phoneNumber,
          ],
        ],
      ],
      'pincodeChange' => [
        'pincode' => $patron->pincode,
        'libraryCardNumber' => $patron->personId,
      ],
    ];

    return $this->request($uri, $payload, RequestMethodInterface::METHOD_PUT);
  }

  /**
   * Create guardian for patron.
   *
   * @param \Drupal\os2forms_fbs_handler\Client\Model\Patron $patron
   *   Patron to create guardian for.
   * @param \Drupal\os2forms_fbs_handler\Client\Model\Guardian $guardian
   *   The guardian to create.
   *
   * @return int
   *   Guardian identifier.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  public function createGuardian(Patron $patron, Guardian $guardian): int {
    $uri = '/external/{agency_id}/patrons/withGuardian/v2';
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
   *   Json response from FBS or TRUE on updatePatron response.
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

    if ($response->getStatusCode() === 204) {
      return TRUE;
    }

    return json_decode($response->getBody(), FALSE, 512, JSON_THROW_ON_ERROR);
  }

}
