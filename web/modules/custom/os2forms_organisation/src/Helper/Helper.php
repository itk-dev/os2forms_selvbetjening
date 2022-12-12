<?php

namespace Drupal\os2forms_organisation\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

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
   * Property accessor.
   *
   * @var \Symfony\Component\PropertyAccess\PropertyAccessor
   */
  private PropertyAccessor $propertyAccessor;

  /**
   * Constructor.
   */
  public function __construct(SecurityTokenService $securityTokenService, OrganisationService $organisationService, ConfigFactoryInterface $configFactory, AccountProxyInterface $account, EntityTypeManagerInterface $entityTypeManager, PropertyAccessor $propertyAccessor) {
    $this->securityTokenService = $securityTokenService;
    $this->organisationService = $organisationService;
    $this->config = $configFactory;
    $this->account = $account;
    $this->entityTypeManager = $entityTypeManager;
    $this->propertyAccessor = $propertyAccessor;
  }

  /**
   * Fetches person name from SF1500.
   */
  public function getPersonName(): string {
    $token = $this->fetchSAMLToken();

    if (NULL === $token) {
      return '';
    }

    // Mit org bruger id.
    $brugerId = $this->getCurrentUserOrganisationId();

    if (NULL === $brugerId) {
      return '';
    }

    $data = $this->brugerLaes($brugerId, $token);

    $personIdKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3RelationListe',
      'ns2TilknyttedePersoner',
      'ns2ReferenceID',
      'ns2UUIDIdentifikator',
    ];

    $personId = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($personIdKeys));

    if (NULL === $personId) {
      return '';
    }

    $data = $this->personLaes($personId, $token);

    $navnTekstKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3AttributListe',
      'ns3Egenskab',
      'ns3NavnTekst',
    ];

    return $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($navnTekstKeys)) ?: '';
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
  public function getOrganisationEnhed(bool $returnOrganisationID = FALSE): ?string {
    $token = $this->fetchSAMLToken();

    if (NULL === $token) {
      return '';
    }

    // Mit org bruger id.
    $orgBrugerId = $this->getCurrentUserOrganisationId();

    if (NULL === $orgBrugerId) {
      return '';
    }

    $data = $this->organisationFunktionSoeg($orgBrugerId, NULL, $token);

    $idListeKeys = [
      'ns3SoegOutput',
      'ns2IdListe',
      'ns2UUIDIdentifikator',
    ];

    $id = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($idListeKeys));

    if (is_array($id)) {
      // @todo HANDLE PEOPLE WITH MORE THAN ONE FUNKTION?
      $id = reset($id);
    }

    if (empty($id)) {
      return '';
    }

    $data = $this->organisationFunktionLaes($id, $token);

    $tilknyttedeEnhederKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3RelationListe',
      'ns2TilknyttedeEnheder',
      'ns2ReferenceID',
      'ns2UUIDIdentifikator',
    ];

    $orgEnhedId = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($tilknyttedeEnhederKeys));

    if ($returnOrganisationID) {
      return $orgEnhedId ?: '';
    }

    $data = $this->organisationEnhedLaes($orgEnhedId, $token);

    $enhedsNavnKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3AttributListe',
      'ns3Egenskab',
      'ns2EnhedNavn',
    ];

    return $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($enhedsNavnKeys)) ?: '';
  }

  /**
   * Fetches organisation enhed level 2 from SF1500.
   */
  public function getOrganisationEnhedNiveauTo() {
    $orgEnhedId = $this->getOrganisationEnhed(TRUE);

    if (empty($orgEnhedId)) {
      return '';
    }

    $token = $this->fetchSAMLToken();

    if (NULL === $token) {
      return '';
    }

    // Level 1.
    $data = $this->organisationEnhedLaes($orgEnhedId, $token);

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
    $orgEnhedId = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($overordnetKeys));

    if (NULL === $orgEnhedId) {
      return '';
    }

    $data = $this->organisationEnhedLaes($orgEnhedId, $token);

    $enhedsNavnKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3AttributListe',
      'ns3Egenskab',
      'ns2EnhedNavn',
    ];

    return $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($enhedsNavnKeys)) ?: '';
  }

  /**
   * Fetches person az ident from SF1500.
   */
  // phpcs:ignore
  public function getPersonAZIdent() {
    $token = $this->fetchSAMLToken();

    if (NULL === $token) {
      return '';
    }

    // Mit org bruger id.
    $brugerId = $this->getCurrentUserOrganisationId();

    if (NULL === $brugerId) {
      return '';
    }

    $response = $this->brugerLaes($brugerId, $token);

    $brugerNavnKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3AttributListe',
      'ns3Egenskab',
      'ns2BrugerNavn',
    ];

    return $this->propertyAccessor->getValue($response, $this->convertKeysToPropertyAccessorFormat($brugerNavnKeys)) ?: '';
  }

  /**
   * Fetches organisation address from SF1500.
   */
  public function getOrganisationAddress() {
    $orgEnhedId = $this->getOrganisationEnhed(TRUE);

    if (empty($orgEnhedId)) {
      return '';
    }

    $token = $this->fetchSAMLToken();

    if (NULL === $token) {
      return '';
    }

    $data = $this->organisationEnhedLaes($orgEnhedId, $token);

    $adresseKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3RelationListe',
      'ns2Adresser',
    ];

    $adresser = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($adresseKeys));

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
      if ('Postadresse' === $this->propertyAccessor->getValue($adresse, $this->convertKeysToPropertyAccessorFormat($adresseRolleLabelKeys))) {

        $adresseId = $this->propertyAccessor->getValue($adresse, $this->convertKeysToPropertyAccessorFormat($adresseReferenceUuidKeys));

        if (NULL === $adresseId) {
          continue;
        }

        $data = $this->adresseLaes($adresseId, $token);
        return $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($adresseTekstKeys)) ?: '';
      }
    }

    return '';
  }

  /**
   * Fetches person magistrat from SF1500.
   */
  public function getPersonMagistrat() {
    $orgEnhedId = $this->getOrganisationEnhed(TRUE);

    if (empty($orgEnhedId)) {
      return '';
    }

    $token = $this->fetchSAMLToken();

    if (NULL === $token) {
      return '';
    }

    $data = $this->organisationEnhedLaes($orgEnhedId, $token);

    $enhedsNavnKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3AttributListe',
      'ns3Egenskab',
      'ns2EnhedNavn',
    ];

    $enhedsNavn = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($enhedsNavnKeys));

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

    while ($orgEnhedId = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($overordnetKeys))) {
      $enhedsNavn = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($enhedsNavnKeys));
      $data = $this->organisationEnhedLaes($orgEnhedId, $token);
    }

    return $enhedsNavn;
  }

  /**
   * Fetches SAML token from SF1514.
   */
  // phpcs:ignore
  private function fetchSAMLToken(): ?string {
    // Organisation config.
    $orgConfig = $this->config->get('os2forms_organisation');

    $endpointSecurityTokenService = $orgConfig->get('security_token_service_endpoint');
    $appliesTo = $orgConfig->get('security_token_service_applies_to');
    $cvr = $orgConfig->get('cvr');
    $public_cert = file_get_contents(DRUPAL_ROOT . $orgConfig->get('public_cert_location'));

    $xml = $this->securityTokenService->buildSAMLTokenRequestXML($public_cert, $this->getPrivateKey(), $cvr, $appliesTo);

    $responseSecurityTokenService = SoapClient::doSOAP($endpointSecurityTokenService, $xml);

    // Parse the RSTR that is returned.
    [$domSecurityTokenService, $xpath, $token] = $this->securityTokenService->parseRequestSecurityTokenResponse($responseSecurityTokenService);

    [$domSecurityTokenService, $token] = $this->securityTokenService->getDecrypted($domSecurityTokenService, $xpath, $token, $this->getPrivateKey());

    return $token != NULL ? $domSecurityTokenService->saveXML($token) : NULL;
  }

  /**
   * Gets certificate private key.
   */
  private function getPrivateKey() {
    $orgConfig = $this->config->get('os2forms_organisation');

    return file_get_contents(DRUPAL_ROOT . $orgConfig->get('priv_key_location'));
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

    return $user->hasField('field_organisation_user_id') ? $user->get('field_organisation_user_id')->value : NULL;
  }

  /**
   * Performs bruger laes action.
   */
  private function brugerLaes($brugerId, $token) {
    $body = $this->organisationService->buildBodyBrugerLaesXML($brugerId);

    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/bruger/6/';
    $action = 'http://kombit.dk/sts/organisation/bruger/laes';

    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);
    $request = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());

    $response = SoapClient::doSOAP($endpoint, $request, $action);

    return $this->responseXMLToArray($response);
  }

  /**
   * Performs adresse laes action.
   */
  private function adresseLaes($adresseID, string $token) {
    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/adresse/6/';
    $action = 'http://kombit.dk/sts/organisation/adresse/laes';

    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);
    $body = $this->organisationService->buildBodyAdresseLaesXML($adresseID);
    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());
    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);

    return $this->responseXMLToArray($response);
  }

  /**
   * Performs organisation enhed laes action.
   */
  private function organisationEnhedLaes($orgEnhedId, string $token) {
    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/organisationenhed/6/';
    $action = 'http://kombit.dk/sts/organisation/organisationenhed/laes';

    $body = $this->organisationService->buildBodyOrganisationEnhedLaesXML($orgEnhedId);
    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());
    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);

    return $this->responseXMLToArray($response);
  }

  /**
   * Performs organisation funktion laes action.
   */
  private function organisationFunktionLaes($orgFunktionId, string $token) {
    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/organisationfunktion/6/';
    $action = 'http://kombit.dk/sts/organisation/organisationfunktion/laes';

    $body = $this->organisationService->buildBodyOrganisationFunktionLaesXML($orgFunktionId);
    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());
    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);

    return $this->responseXMLToArray($response);
  }

  /**
   * Performs organisation funktion soeg action.
   */
  private function organisationFunktionSoeg($orgBrugerId, $funktionsNavn, string $token) {
    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/organisationfunktion/6/';
    $action = 'http://kombit.dk/sts/organisation/organisationfunktion/soeg';

    $body = $this->organisationService->buildBodyOrganisationFunktionSoegXML($orgBrugerId, $funktionsNavn, NULL);
    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());
    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);

    return $this->responseXMLToArray($response);
  }

  /**
   * Performs person laes action.
   */
  private function personLaes($personId, string $token) {
    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/person/6/';
    $action = 'http://kombit.dk/sts/organisation/person/laes';

    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);
    $body = $this->organisationService->buildBodyPersonLaesXML($personId);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());
    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);

    return $this->responseXMLToArray($response);
  }

  /**
   * Fetches bruger adresse attribut.
   */
  private function getBrugerAdresseAttribut(string $attribute) {
    $token = $this->fetchSAMLToken();

    if (NULL === $token) {
      return '';
    }

    // Mit org bruger id.
    $brugerId = $this->getCurrentUserOrganisationId();

    if (NULL === $brugerId) {
      return '';
    }

    $data = $this->brugerLaes($brugerId, $token);

    $adresseKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3RelationListe',
      'ns2Adresser',
    ];

    $adresser = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($adresseKeys));

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
      if ($this->propertyAccessor->getValue($adresse, $this->convertKeysToPropertyAccessorFormat($adresseRolleLabelKeys)) === $attribute) {

        $adresseId = $this->propertyAccessor->getValue($adresse, $this->convertKeysToPropertyAccessorFormat($adresseReferenceUuidKeys));

        if (NULL === $adresseId) {
          continue;
        }

        $data = $this->adresseLaes($adresseId, $token);
        return $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($adresseTekstKeys)) ?: '';
      }
    }

    return '';
  }

  /**
   * Converts keys into Symfony PropertyAccessor property path format.
   *
   * @see https://symfony.com/doc/current/components/property_access.html#reading-from-arrays
   *
   * @example
   * $keys = [
   *   'some_special_key',
   *   'some_other_special_key'
   * ];
   *
   * convertKeysToPropertyAccessorFormat($keys) =
   *  '[some_special_key][some_other_special_key]'.
   */
  private function convertKeysToPropertyAccessorFormat(array $keys): string {
    $value = '';

    foreach ($keys as $key) {
      $value .= '[' . $key . ']';
    }

    return $value;
  }

}
