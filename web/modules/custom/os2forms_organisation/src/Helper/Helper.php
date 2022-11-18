<?php

namespace Drupal\os2forms_organisation\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Helper for integrating to SF1500 Organisation.
 */
class Helper {
  const ISSUER = 'https://adgangsstyring.eksterntest-stoettesystemerne.dk/';

  /**
   * SF1514 Security Token Service helper.
   *
   * @var \Drupal\os2forms_organisation\Helper\SecurityTokenService
   */
  private SecurityTokenService $securityTokenService;

  /**
   * SF1500 Organisation XML helper.
   *
   * @var \Drupal\os2forms_organisation\Helper\OrganisationService
   */
  private OrganisationService $organisationService;

  /**
   * The ConfigFactoryInterface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $config;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $account;

  /**
   * The EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(SecurityTokenService $securityTokenService, OrganisationService $organisationService, ConfigFactoryInterface $configFactory, AccountProxyInterface $account, EntityTypeManagerInterface $entityTypeManager) {
    $this->securityTokenService = $securityTokenService;
    $this->organisationService = $organisationService;
    $this->config = $configFactory;
    $this->account = $account;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Fetches person name from SF1500.
   */
  public function getPersonName(): string {
    $token = $this->fetchSAMLToken();

    if ($token === NULL) {
      return '';
    }

    // Mit org bruger id.
    $brugerId = $this->getCurrentUserOrganisationId();

    if ($brugerId === NULL) {
      return '';
    }

    $responseArray = $this->brugerLaes($brugerId, $token);

    $personIdKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3RelationListe',
      'ns2TilknyttedePersoner',
      'ns2ReferenceID',
      'ns2UUIDIdentifikator',
    ];

    $personId = $this->checkKeyOrderExistsInArray($personIdKeys, $responseArray, TRUE);

    if (!$personId) {
      return '';
    }

    $responseArray = $this->personLaes($personId, $token);

    $navnTekstKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3AttributListe',
      'ns3Egenskab',
      'ns3NavnTekst',
    ];

    return $this->checkKeyOrderExistsInArray($navnTekstKeys, $responseArray, TRUE) ?: '';
  }

  /**
   * Fetches person phone from SF1500.
   */
  public function getPersonPhone(): string {
    return $this->getBrugerAdresseAttribut('Mobiltelefon_bruger');
  }

  /**
   * Fetches person location from SF1500.
   */
  public function getPersonLocation() {
    return $this->getBrugerAdresseAttribut('Lokation_bruger');
  }

  /**
   * Fetches person email from SF1500.
   */
  public function getPersonEmail(): string {
    return $this->getBrugerAdresseAttribut('Email_bruger');
  }

  /**
   * Fetches organisation enhed level 1 name from SF1500.
   */
  public function getOrganisationEnhed(bool $returnOrganisationID = FALSE): string {
    $token = $this->fetchSAMLToken();

    if ($token === NULL) {
      return '';
    }

    // Mit org bruger id.
    $orgBrugerId = $this->getCurrentUserOrganisationId();

    if ($orgBrugerId === NULL) {
      return '';
    }

    $responseArray = $this->organisationFunktionSoeg($orgBrugerId, NULL, $token);

    $idListeKeys = [
      'ns3SoegOutput',
      'ns2IdListe',
      'ns2UUIDIdentifikator',
    ];

    $id = $this->checkKeyOrderExistsInArray($idListeKeys, $responseArray, TRUE);

    if (is_array($id)) {
      // @todo HANDLE PEOPLE WITH MORE THAN ONE FUKNTION?
      $id = $id[0];
    }

    if ($id === NULL) {
      return '';
    }

    $responseArray = $this->organisationFunktionLaes($id, $token);

    $tilknyttedeEnhederKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3RelationListe',
      'ns2TilknyttedeEnheder',
      'ns2ReferenceID',
      'ns2UUIDIdentifikator',
    ];

    $orgEnhedId = $this->checkKeyOrderExistsInArray($tilknyttedeEnhederKeys, $responseArray, TRUE);

    if ($returnOrganisationID) {
      return $orgEnhedId ?: '';
    }

    $responseArray = $this->organisationEnhedLaes($orgEnhedId, $token);

    $enhedsNavnKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3AttributListe',
      'ns3Egenskab',
      'ns2EnhedNavn',
    ];

    return $this->checkKeyOrderExistsInArray($enhedsNavnKeys, $responseArray, TRUE) ?: '';
  }

  /**
   * Fetches organisation enhed level 2 from SF1500.
   */
  public function getOrganisationEnhedNiveauTo() {
    $orgEnhedId = $this->getOrganisationEnhed(TRUE);

    if (!$orgEnhedId) {
      return '';
    }

    $token = $this->fetchSAMLToken();

    if ($token === NULL) {
      return '';
    }

    // Level 1.
    $responseArray = $this->organisationEnhedLaes($orgEnhedId, $token);

    $overordnetKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3RelationListe',
      'ns2Overordnet',
      'ns2ReferenceID',
      'ns2UUIDIdentifikator',
    ];

    // Level 2.
    $orgEnhedId = $this->checkKeyOrderExistsInArray($overordnetKeys, $responseArray, TRUE);

    if (!$orgEnhedId) {
      return '';
    }

    $responseArray = $this->organisationEnhedLaes($orgEnhedId, $token);

    $enhedsNavnKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3AttributListe',
      'ns3Egenskab',
      'ns2EnhedNavn',
    ];

    return $this->checkKeyOrderExistsInArray($enhedsNavnKeys, $responseArray, TRUE) ?: '';
  }

  /**
   * Fetches person az ident from SF1500.
   */
  // phpcs:ignore
  public function getPersonAZIdent() {
    $token = $this->fetchSAMLToken();

    if ($token === NULL) {
      return '';
    }

    // Mit org bruger id.
    $brugerId = $this->getCurrentUserOrganisationId();

    if ($brugerId === NULL) {
      return '';
    }

    $responseArray = $this->brugerLaes($brugerId, $token);

    $brugerNavnKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3AttributListe',
      'ns3Egenskab',
      'ns2BrugerNavn',
    ];

    return $this->checkKeyOrderExistsInArray($brugerNavnKeys, $responseArray, TRUE);
  }

  /**
   * Fetches organisation address from SF1500.
   */
  public function getOrganisationAddress() {
    $orgEnhedId = $this->getOrganisationEnhed(TRUE);

    if (!$orgEnhedId) {
      return '';
    }

    $token = $this->fetchSAMLToken();

    if ($token === NULL) {
      return '';
    }

    $responseArray = $this->organisationEnhedLaes($orgEnhedId, $token);

    $adresseKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3RelationListe',
      'ns2Adresser',
    ];

    $adresser = $this->checkKeyOrderExistsInArray($adresseKeys, $responseArray, TRUE);

    if (!is_array($adresser)) {
      return '';
    }

    $adresseTekstKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3AttributListe',
      'ns3Egenskab',
      'ns4AdresseTekst',
    ];

    $adresseRolleLabelKeys = [
      'ns2Rolle',
      'ns2Label',
    ];

    $adresseReferenceUuidKeys = [
      'ns2ReferenceID',
      'ns2UUIDIdentifikator',
    ];

    foreach ($adresser as $adresse) {
      if ($this->checkKeyOrderExistsInArray($adresseRolleLabelKeys, $adresse, TRUE) === 'Postadresse') {

        $adresseId = $this->checkKeyOrderExistsInArray($adresseReferenceUuidKeys, $adresse, TRUE);

        if (!$adresseId) {
          continue;
        }

        $responseArray = $this->adresseLaes($adresseId, $token);
        return $this->checkKeyOrderExistsInArray($adresseTekstKeys, $responseArray, TRUE) ?: '';
      }
    }

    return '';
  }

  /**
   * Fetches person magistrat from SF1500.
   */
  public function getPersonMagistrat() {
    $orgEnhedId = $this->getOrganisationEnhed(TRUE);

    if (!$orgEnhedId) {
      return '';
    }

    $token = $this->fetchSAMLToken();

    if ($token === NULL) {
      return '';
    }

    $responseArray = $this->organisationEnhedLaes($orgEnhedId, $token);

    $enhedsNavnKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3AttributListe',
      'ns3Egenskab',
      'ns2EnhedNavn',
    ];
    $enhedsNavn = $this->checkKeyOrderExistsInArray($enhedsNavnKeys, $responseArray, TRUE);

    // Follow organisation until parent does not exist, updating $enhedsNavn.
    $overordnetKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3RelationListe',
      'ns2Overordnet',
      'ns2ReferenceID',
      'ns2UUIDIdentifikator',
    ];

    while ($orgEnhedId = $this->checkKeyOrderExistsInArray($overordnetKeys, $responseArray, TRUE)) {
      $enhedsNavn = $this->checkKeyOrderExistsInArray($enhedsNavnKeys, $responseArray, TRUE);
      $responseArray = $this->organisationEnhedLaes($orgEnhedId, $token);
    }

    return $enhedsNavn;
  }

  /**
   * Fetches SAML token from SF1514.
   */
  // phpcs:ignore
  private function fetchSAMLToken(): ?string {
    $appliesTo = 'http://stoettesystemerne.dk/service/organisation/3';
    // Aarhus kommune cvr.
    $cvr = '55133018';

    // Endpoint.
    $endpointSecurityTokenService = 'https://adgangsstyring.eksterntest-stoettesystemerne.dk/runtime/services/kombittrust/14/certificatemixed';

    $orgConfig = $this->config->get('os2forms_organisation');

    $public_cert = file_get_contents(__DIR__ . '/../../' . $orgConfig->get('public_cert_location'));

    $requestSecurityTokenService = $this->securityTokenService->getRequestSecurityTokenXML($endpointSecurityTokenService, $appliesTo, $cvr, self::ISSUER, $public_cert);
    $requestSecurityTokenServiceSigned = $this->securityTokenService->signRequestSecurityToken($requestSecurityTokenService, $this->getPrivateKey());
    $responseSecurityTokenService = SoapClient::doSOAP($endpointSecurityTokenService, $requestSecurityTokenServiceSigned);

    // Parse the RSTR that is returned.
    [$domSecurityTokenService, $xpath, $token] = $this->securityTokenService->parseRequestSecurityTokenResponse($responseSecurityTokenService);

    [$domSecurityTokenService, $token] = $this->securityTokenService->getDecrypted($domSecurityTokenService, $xpath, $token, $this->getPrivateKey());

    if ($token != NULL) {
      return $domSecurityTokenService->saveXML($token);
    }

    return NULL;
  }

  /**
   * Gets certificate private key.
   */
  private function getPrivateKey() {
    $orgConfig = $this->config->get('os2forms_organisation');

    return file_get_contents(__DIR__ . '/../../' . $orgConfig->get('priv_key_location'));
  }

  /**
   * Creates XML request.
   */
  // phpcs:ignore
  private function createXMLRequest(string $header, string $body): string {
    return <<<XML
    <s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope" xmlns:a="http://www.w3.org/2005/08/addressing" xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">$header$body</s:Envelope>
    XML;
  }

  /**
   * Converts XML response to array.
   */
  // phpcs:ignore
  private function responseXMLToArray(string $response) {
    // Handle xml namespaces.
    $response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response);
    $xml = simplexml_load_string($response);
    $body = $xml->xpath('//soapBody')[0];
    return json_decode(json_encode((array) $body), TRUE);
  }

  /**
   * Fetches current user organisation user id.
   */
  private function getCurrentUserOrganisationId() {
    $user = $this->entityTypeManager->getStorage('user')->load($this->account->id());

    if ($user->hasField('field_organisation_user_id')) {
      return $user->get('field_organisation_user_id')->value;
    }
    else {
      return NULL;
    }
  }

  /**
   * Performs bruger laes action.
   */
  private function brugerLaes($brugerId, $token) {
    $body = $this->organisationService->getBodyBrugerLaes($brugerId);

    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/bruger/6/';
    $action = 'http://kombit.dk/sts/organisation/bruger/laes';

    $header = $this->organisationService->getHeader($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);
    $requestSigned = $this->organisationService->getRequestSigned($request, $this->getPrivateKey());

    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);

    return $this->responseXMLToArray($response);
  }

  /**
   * Performs adresse laes action.
   */
  private function adresseLaes($adresseID, string $token) {
    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/adresse/6/';
    $action = 'http://kombit.dk/sts/organisation/adresse/laes';

    $header = $this->organisationService->getHeader($endpoint, $action, $token);
    $body = $this->organisationService->getBodyAdresseLaes($adresseID);
    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->getRequestSigned($request, $this->getPrivateKey());
    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);

    return $this->responseXMLToArray($response);
  }

  /**
   * Performs organisation enhed laes action.
   */
  private function organisationEnhedLaes($orgEnhedId, string $token) {
    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/organisationenhed/6/';
    $action = 'http://kombit.dk/sts/organisation/organisationenhed/laes';

    $body = $this->organisationService->getBodyOrganisationEnhedLaes($orgEnhedId);
    $header = $this->organisationService->getHeader($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->getRequestSigned($request, $this->getPrivateKey());
    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);

    return $this->responseXMLToArray($response);
  }

  /**
   * Performs organisation funktion laes action.
   */
  private function organisationFunktionLaes($orgFunktionId, string $token) {
    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/organisationfunktion/6/';
    $action = 'http://kombit.dk/sts/organisation/organisationfunktion/laes';

    $body = $this->organisationService->getBodyOrganisationFunktionLaes($orgFunktionId);
    $header = $this->organisationService->getHeader($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->getRequestSigned($request, $this->getPrivateKey());
    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);

    return $this->responseXMLToArray($response);
  }

  /**
   * Performs organisation funktion soeg action.
   */
  private function organisationFunktionSoeg($orgBrugerId, $funktionsNavn, string $token) {
    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/organisationfunktion/6/';
    $action = 'http://kombit.dk/sts/organisation/organisationfunktion/soeg';

    $body = $this->organisationService->getBodyOrganisationFunktionSoeg($orgBrugerId, $funktionsNavn);
    $header = $this->organisationService->getHeader($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->getRequestSigned($request, $this->getPrivateKey());
    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);

    return $this->responseXMLToArray($response);
  }

  /**
   * Performs person laes action.
   */
  private function personLaes($personId, string $token) {
    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/person/6/';
    $action = 'http://kombit.dk/sts/organisation/person/laes';

    $header = $this->organisationService->getHeader($endpoint, $action, $token);
    $body = $this->organisationService->getBodyPersonLaes($personId);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->getRequestSigned($request, $this->getPrivateKey());
    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);

    return $this->responseXMLToArray($response);
  }

  /**
   * Fetches bruger adresse attribut.
   */
  private function getBrugerAdresseAttribut(string $attribute) {
    $token = $this->fetchSAMLToken();

    if ($token === NULL) {
      return '';
    }

    // Mit org bruger id.
    $brugerId = $this->getCurrentUserOrganisationId();

    if ($brugerId === NULL) {
      return '';
    }

    $responseArray = $this->brugerLaes($brugerId, $token);

    $adresseKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3RelationListe',
      'ns2Adresser',
    ];

    $adresser = $this->checkKeyOrderExistsInArray($adresseKeys, $responseArray, TRUE);

    if (!is_array($adresser)) {
      return '';
    }

    $adresseTekstKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3AttributListe',
      'ns3Egenskab',
      'ns4AdresseTekst',
    ];

    $adresseRolleLabelKeys = [
      'ns2Rolle',
      'ns2Label',
    ];

    $adresseReferenceUuidKeys = [
      'ns2ReferenceID',
      'ns2UUIDIdentifikator',
    ];

    foreach ($adresser as $adresse) {
      if ($this->checkKeyOrderExistsInArray($adresseRolleLabelKeys, $adresse, TRUE) === $attribute) {

        $adresseId = $this->checkKeyOrderExistsInArray($adresseReferenceUuidKeys, $adresse, TRUE);

        if (!$adresseId) {
          continue;
        }

        $responseArray = $this->adresseLaes($adresseId, $token);
        return $this->checkKeyOrderExistsInArray($adresseTekstKeys, $responseArray, TRUE) ?: '';
      }
    }

    return '';
  }

  /**
   * Checks if specific order of keys exists in nested array.
   *
   * @example
   * $haystack = [
   *   'a' => [
   *     'a1' => ['a11', 'a12'],
   *     'a2' => ['a21', 'a22'],
   *   ],
   *   'b' => [
   *     'b1' => ['b11', 'b12'],
   *     'b2' => ['b21', 'b22'],
   *   ],
   * ];
   *
   * checkKeyOrderExistsInArray(['a','a1'], $haystack) = true
   * checkKeyOrderExistsInArray(['a1','a'], $haystack) = false
   * checkKeyOrderExistsInArray(['a','b1'], $haystack) = false
   * checkKeyOrderExistsInArray(['a','a1', 'a11'], $haystack) = false
   */
  private function checkKeyOrderExistsInArray(array $keys, array $haystack, bool $getValue = FALSE) {
    foreach ($keys as $key) {
      if (!array_key_exists($key, $haystack)) {
        return FALSE;
      }
      $haystack = $haystack[$key];
    }

    return $getValue ? $haystack : TRUE;
  }

}
