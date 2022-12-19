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

    $personId = $this->getValue($data, $personIdKeys, '');

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

    return $this->getValue($data, $navnTekstKeys, '');
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
   * Fetches organisations funktioner from SF1500.
   */
  public function getOrganisationFunktioner() {
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

    $id = $this->getValue($data, $idListeKeys);

    return $id;
  }

  /**
   * Fetches organisation enhed level 1 name from SF1500.
   */
  public function getOrganisationEnhed(string $funktionsId, bool $returnOrganisationID = FALSE): ?string {
    $token = $this->fetchSAMLToken();

    $data = $this->organisationFunktionLaes($funktionsId, $token);

    $tilknyttedeEnhederKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3RelationListe',
      'ns2TilknyttedeEnheder',
      'ns2ReferenceID',
      'ns2UUIDIdentifikator',
    ];

    $orgEnhedId = $this->getValue($data, $tilknyttedeEnhederKeys);

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

    return $this->getValue($data, $enhedsNavnKeys, '');
  }

  /**
   * Fetches funktions navn from SF1500.
   */
  public function getFunktionsNavn(string $funktionsId) {
    $token = $this->fetchSAMLToken();

    $data = $this->organisationFunktionLaes($funktionsId, $token);

    $funktionsNavnKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3AttributListe',
      'ns3Egenskab',
      'ns2FunktionNavn',
    ];

    return $this->getValue($data, $funktionsNavnKeys, '');
  }

  /**
   * Fetches organisation enhed level 2 from SF1500.
   */
  public function getOrganisationEnhedNiveauTo(string $id) {
    $orgEnhedId = $this->getOrganisationEnhed($id, TRUE);

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
    $orgEnhedId = $this->getValue($data, $overordnetKeys);

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

    return $this->getValue($data, $enhedsNavnKeys, '');
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

    $data = $this->brugerLaes($brugerId, $token);

    $brugerNavnKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3AttributListe',
      'ns3Egenskab',
      'ns2BrugerNavn',
    ];

    return $this->getValue($data, $brugerNavnKeys, '');
  }

  /**
   * Fetches organisation address from SF1500.
   */
  public function getOrganisationAddress(string $id) {
    $orgEnhedId = $this->getOrganisationEnhed($id, TRUE);

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

    $adresser = $this->getValue($data, $adresseKeys);

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
      if ('Postadresse' === $this->getValue($adresse, $adresseRolleLabelKeys)) {

        $adresseId = $this->getValue($adresse, $adresseReferenceUuidKeys);

        if (NULL === $adresseId) {
          continue;
        }

        $data = $this->adresseLaes($adresseId, $token);

        return $this->getValue($data, $adresseTekstKeys, '');
      }
    }

    return '';
  }

  /**
   * Fetches person magistrat from SF1500.
   */
  public function getPersonMagistrat(string $id) {
    $orgEnhedId = $this->getOrganisationEnhed($id, TRUE);

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

    $enhedsNavn = $this->getValue($data, $enhedsNavnKeys);

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

    while ($orgEnhedId = $this->getValue($data, $overordnetKeys)) {
      $enhedsNavn = $this->getValue($data, $enhedsNavnKeys);
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

    $adresser = $this->getValue($data, $adresseKeys);

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
      if ($this->getValue($adresse, $adresseRolleLabelKeys) === $attribute) {

        $adresseId = $this->getValue($adresse, $adresseReferenceUuidKeys);

        if (NULL === $adresseId) {
          continue;
        }

        $data = $this->adresseLaes($adresseId, $token);

        return $this->getValue($data, $adresseTekstKeys, '');
      }
    }

    return '';
  }

  /**
   * Gets value from data according to keys.
   */
  private function getValue($data, array $keys, $defaultValue = NULL) {

    // @see https://symfony.com/doc/current/components/property_access.html#reading-from-arrays
    $propertyPath = '[' . implode('][', $keys) . ']';

    if ($this->propertyAccessor->isReadable($data, $propertyPath)) {
      return $this->propertyAccessor->getValue($data, $propertyPath) ?: $defaultValue;
    }

    return $defaultValue;
  }

}
