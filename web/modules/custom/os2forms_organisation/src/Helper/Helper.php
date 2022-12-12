<?php

namespace Drupal\os2forms_organisation\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
<<<<<<< HEAD
<<<<<<< HEAD
use Symfony\Component\PropertyAccess\PropertyAccessor;
=======
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
use Symfony\Component\PropertyAccess\PropertyAccessor;
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

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
<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
   * Property accessor.
   *
   * @var \Symfony\Component\PropertyAccess\PropertyAccessor
   */
  private PropertyAccessor $propertyAccessor;

  /**
<<<<<<< HEAD
   * Constructor.
   */
  public function __construct(SecurityTokenService $securityTokenService, OrganisationService $organisationService, ConfigFactoryInterface $configFactory, AccountProxyInterface $account, EntityTypeManagerInterface $entityTypeManager, PropertyAccessor $propertyAccessor) {
=======
   * Constructor.
   */
  public function __construct(SecurityTokenService $securityTokenService, OrganisationService $organisationService, ConfigFactoryInterface $configFactory, AccountProxyInterface $account, EntityTypeManagerInterface $entityTypeManager) {
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
   * Constructor.
   */
  public function __construct(SecurityTokenService $securityTokenService, OrganisationService $organisationService, ConfigFactoryInterface $configFactory, AccountProxyInterface $account, EntityTypeManagerInterface $entityTypeManager, PropertyAccessor $propertyAccessor) {
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
    $this->securityTokenService = $securityTokenService;
    $this->organisationService = $organisationService;
    $this->config = $configFactory;
    $this->account = $account;
    $this->entityTypeManager = $entityTypeManager;
<<<<<<< HEAD
<<<<<<< HEAD
    $this->propertyAccessor = $propertyAccessor;
=======
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
    $this->propertyAccessor = $propertyAccessor;
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
  }

  /**
   * Fetches person name from SF1500.
   */
  public function getPersonName(): string {
    $token = $this->fetchSAMLToken();

<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
    if (NULL === $token) {
      return '';
=======
    if ($token === NULL) {
      return 'Something went wrong collecting token';
>>>>>>> 2552f8f (DW-454: Organisationsdata)
    }

    // Mit org bruger id.
    $brugerId = $this->getCurrentUserOrganisationId();

<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
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

<<<<<<< HEAD
    $personId = $this->getValue($data, $personIdKeys, '');
=======
    $personId = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($personIdKeys));
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

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

<<<<<<< HEAD
    return $this->getValue($data, $navnTekstKeys, '');
=======
    if ($brugerId === NULL) {
      return 'Something went wrong collecting organisation user id';
    }

    $responseArray = $this->brugerLaes($brugerId, $token);

    try {
      $personId = $responseArray['ns3LaesOutput']['ns3FiltreretOejebliksbillede']['ns3Registrering']['ns3RelationListe']['ns2TilknyttedePersoner']['ns2ReferenceID']['ns2UUIDIdentifikator'];

      $responseArray = $this->personLaes($personId, $token);

      $name = $responseArray['ns3LaesOutput']['ns3FiltreretOejebliksbillede']['ns3Registrering']['ns3AttributListe']['ns3Egenskab']['ns3NavnTekst'];

      return $name ?? 'woopsie';

    }
    catch (\Exception $exception) {
      // Something went wrong.
      return 'Something went wrong';
    }
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
    return $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($navnTekstKeys)) ?: '';
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
  }

  /**
   * Fetches person phone from SF1500.
   */
  public function getPersonPhone(): string {
<<<<<<< HEAD
    return $this->getBrugerAdresseAttribut('Mobiltelefon_bruger');
  }

  /**
   * Fetches person location from SF1500.
   */
  public function getPersonLocation() {
    return $this->getBrugerAdresseAttribut('Lokation_bruger');
=======
    $token = $this->fetchSAMLToken();

    if ($token === NULL) {
      return 'Something went wrong collecting token';
    }

    // Mit org bruger id.
    $brugerId = $this->getCurrentUserOrganisationId();

    if ($brugerId === NULL) {
      return 'Something went wrong collecting organisation user id';
    }

    $responseArray = $this->brugerLaes($brugerId, $token);

    try {
      $adresser = $responseArray['ns3LaesOutput']['ns3FiltreretOejebliksbillede']['ns3Registrering']['ns3RelationListe']['ns2Adresser'];

      foreach ($adresser as $adresse) {
        if ($adresse['ns2Rolle']['ns2Label'] === 'Mobiltelefon_bruger') {

          $responseArray = $this->adresseLaes($adresse['ns2ReferenceID']['ns2UUIDIdentifikator'], $token);

          $phone = $responseArray['ns3LaesOutput']['ns3FiltreretOejebliksbillede']['ns3Registrering']['ns3AttributListe']['ns3Egenskab']['ns4AdresseTekst'];

          return $phone ?? 'woopsie';
        }
      }

      return 'Phone not configured';

    }
    catch (\Exception $exception) {
      // Something went wrong.
      return 'Something went wrong';
    }
>>>>>>> 2552f8f (DW-454: Organisationsdata)
  }

  /**
   * Fetches person email from SF1500.
   */
  public function getPersonEmail(): string {
<<<<<<< HEAD
    return $this->getBrugerAdresseAttribut('Email_bruger');
  }

  /**
   * Fetches organisations funktioner from SF1500.
   */
<<<<<<< HEAD
  public function getOrganisationFunktioner() {
=======
  public function getOrganisationEnhed(bool $returnOrganisationID = FALSE): ?string {
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
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

<<<<<<< HEAD
    $id = $this->getValue($data, $idListeKeys);

    return $id;
=======
    $token = $this->fetchSAMLToken();

    if ($token === NULL) {
      return 'Something went wrong collecting token';
    }

    // Mit org bruger id.
    $brugerId = $this->getCurrentUserOrganisationId();

    if ($brugerId === NULL) {
      return 'Something went wrong collecting organisation user id';
    }

    $responseArray = $this->brugerLaes($brugerId, $token);

    try {
      $adresser = $responseArray['ns3LaesOutput']['ns3FiltreretOejebliksbillede']['ns3Registrering']['ns3RelationListe']['ns2Adresser'];

      foreach ($adresser as $adresse) {
        if ($adresse['ns2Rolle']['ns2Label'] === 'Email_bruger') {

          $responseArray = $this->adresseLaes($adresse['ns2ReferenceID']['ns2UUIDIdentifikator'], $token);

          $email = $responseArray['ns3LaesOutput']['ns3FiltreretOejebliksbillede']['ns3Registrering']['ns3AttributListe']['ns3Egenskab']['ns4AdresseTekst'];

          return $email ?? 'woopsie';
        }
      }

    }
    catch (\Exception $exception) {
      // Something went wrong.
      return 'Something went wrong';
    }

    return 'something went wrong';
>>>>>>> 2552f8f (DW-454: Organisationsdata)
  }

  /**
   * Fetches organisation enhed level 1 name from SF1500.
   */
<<<<<<< HEAD
  public function getOrganisationEnhed(string $funktionsId, bool $returnOrganisationID = FALSE): ?string {
    $token = $this->fetchSAMLToken();

    $data = $this->organisationFunktionLaes($funktionsId, $token);
=======
    $id = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($idListeKeys));

    if (is_array($id)) {
      // @todo HANDLE PEOPLE WITH MORE THAN ONE FUNKTION?
      $id = reset($id);
    }

    if (empty($id)) {
      return '';
    }

    $data = $this->organisationFunktionLaes($id, $token);
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

    $tilknyttedeEnhederKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3RelationListe',
      'ns2TilknyttedeEnheder',
      'ns2ReferenceID',
      'ns2UUIDIdentifikator',
    ];

<<<<<<< HEAD
    $orgEnhedId = $this->getValue($data, $tilknyttedeEnhederKeys);
=======
    $orgEnhedId = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($tilknyttedeEnhederKeys));
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

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

<<<<<<< HEAD
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
=======
    return $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($enhedsNavnKeys)) ?: '';
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
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
<<<<<<< HEAD
    $orgEnhedId = $this->getValue($data, $overordnetKeys);
=======
    $orgEnhedId = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($overordnetKeys));
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

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

<<<<<<< HEAD
    return $this->getValue($data, $enhedsNavnKeys, '');
=======
  public function getOrganisationEnhed(bool $returnOrganisationID = FALSE): string {
    $token = $this->fetchSAMLToken();

    if ($token === NULL) {
      return 'Something went wrong collecting token';
    }

    // Mit org bruger id.
    $orgBrugerId = $this->getCurrentUserOrganisationId();

    if ($orgBrugerId === NULL) {
      return 'Something went wrong collecting organisation user id';
    }

    $responseArray = $this->organisationFunktionSoeg($orgBrugerId, NULL, $token);

    $id = $responseArray['ns3SoegOutput']['ns2IdListe']['ns2UUIDIdentifikator'];

    if (is_array($id)) {
      // @todo HANDLE PEOPLE WITH MORE THAN ONE FUKNTION?
      $id = $id[0];
    }

    if ($id === NULL) {
      return 'Something went wrong';
    }

    $responseArray = $this->organisationFunktionLaes($id, $token);

    $orgEnhedId = $responseArray['ns3LaesOutput']['ns3FiltreretOejebliksbillede']['ns3Registrering']['ns3RelationListe']['ns2TilknyttedeEnheder']['ns2ReferenceID']['ns2UUIDIdentifikator'];

    if ($returnOrganisationID) {
      return $orgEnhedId;
    }

    $responseArray = $this->organisationEnhedLaes($orgEnhedId, $token);

    return $responseArray['ns3LaesOutput']['ns3FiltreretOejebliksbillede']['ns3Registrering']['ns3AttributListe']['ns3Egenskab']['ns2EnhedNavn'];
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
    return $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($enhedsNavnKeys)) ?: '';
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
  }

  /**
   * Fetches person az ident from SF1500.
   */
  // phpcs:ignore
  public function getPersonAZIdent() {
    $token = $this->fetchSAMLToken();

<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
    if (NULL === $token) {
      return '';
=======
    if ($token === NULL) {
      return 'Something went wrong collecting token';
>>>>>>> 2552f8f (DW-454: Organisationsdata)
    }

    // Mit org bruger id.
    $brugerId = $this->getCurrentUserOrganisationId();

<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
    if (NULL === $brugerId) {
      return '';
    }

<<<<<<< HEAD
    $data = $this->brugerLaes($brugerId, $token);
=======
    $response = $this->brugerLaes($brugerId, $token);
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

    $brugerNavnKeys = [
      'ns3LaesOutput',
      'ns3FiltreretOejebliksbillede',
      'ns3Registrering',
      'ns3AttributListe',
      'ns3Egenskab',
      'ns2BrugerNavn',
    ];

<<<<<<< HEAD
    return $this->getValue($data, $brugerNavnKeys, '');
=======
    if ($brugerId === NULL) {
      return 'Something went wrong collecting organisation user id';
    }

    $responseArray = $this->brugerLaes($brugerId, $token);

    return $responseArray['ns3LaesOutput']['ns3FiltreretOejebliksbillede']['ns3Registrering']['ns3AttributListe']['ns3Egenskab']['ns2BrugerNavn'];
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
    return $this->propertyAccessor->getValue($response, $this->convertKeysToPropertyAccessorFormat($brugerNavnKeys)) ?: '';
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
  }

  /**
   * Fetches organisation address from SF1500.
   */
<<<<<<< HEAD
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

<<<<<<< HEAD
    $adresser = $this->getValue($data, $adresseKeys);
=======
    $adresser = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($adresseKeys));
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

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
<<<<<<< HEAD
      if ('Postadresse' === $this->getValue($adresse, $adresseRolleLabelKeys)) {

        $adresseId = $this->getValue($adresse, $adresseReferenceUuidKeys);
=======
      if ('Postadresse' === $this->propertyAccessor->getValue($adresse, $this->convertKeysToPropertyAccessorFormat($adresseRolleLabelKeys))) {

        $adresseId = $this->propertyAccessor->getValue($adresse, $this->convertKeysToPropertyAccessorFormat($adresseReferenceUuidKeys));
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

        if (NULL === $adresseId) {
          continue;
        }

        $data = $this->adresseLaes($adresseId, $token);
<<<<<<< HEAD

        return $this->getValue($data, $adresseTekstKeys, '');
=======
        return $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($adresseTekstKeys)) ?: '';
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
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

<<<<<<< HEAD
    $enhedsNavn = $this->getValue($data, $enhedsNavnKeys);
=======
    $enhedsNavn = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($enhedsNavnKeys));
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

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

<<<<<<< HEAD
    while ($orgEnhedId = $this->getValue($data, $overordnetKeys)) {
      $enhedsNavn = $this->getValue($data, $enhedsNavnKeys);
=======
    while ($orgEnhedId = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($overordnetKeys))) {
      $enhedsNavn = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($enhedsNavnKeys));
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
      $data = $this->organisationEnhedLaes($orgEnhedId, $token);
    }

    return $enhedsNavn;
=======
  public function getOrganisationAddress() {
    $orgID = $this->getOrganisationEnhed(TRUE);

    $token = $this->fetchSAMLToken();

    if ($token === NULL) {
      return 'Something went wrong collecting token';
    }

    $responseArray = $this->organisationEnhedLaes($orgID, $token);

    $adresser = $responseArray['ns3LaesOutput']['ns3FiltreretOejebliksbillede']['ns3Registrering']['ns3RelationListe']['ns2Adresser'];

    $adresseID = NULL;
    foreach ($adresser as $adresse) {
      if ($adresse['ns2Rolle']['ns2Label'] === 'Postadresse') {
        $adresseID = $adresse['ns2ReferenceID']['ns2UUIDIdentifikator'];
      }
    }

    if (NULL === $adresseID) {
      return 'something went wrong';
    }

    $responseArray = $this->adresseLaes($adresseID, $token);

    return $responseArray['ns3LaesOutput']['ns3FiltreretOejebliksbillede']['ns3Registrering']['ns3AttributListe']['ns3Egenskab']['ns4AdresseTekst'];
>>>>>>> 2552f8f (DW-454: Organisationsdata)
  }

  /**
   * Fetches SAML token from SF1514.
   */
  // phpcs:ignore
  private function fetchSAMLToken(): ?string {
<<<<<<< HEAD
<<<<<<< HEAD
    // Organisation config.
    $orgConfig = $this->config->get('os2forms_organisation');

    $endpointSecurityTokenService = $orgConfig->get('security_token_service_endpoint');
    $appliesTo = $orgConfig->get('security_token_service_applies_to');
    $cvr = $orgConfig->get('cvr');
    $public_cert = file_get_contents(DRUPAL_ROOT . $orgConfig->get('public_cert_location'));

    $xml = $this->securityTokenService->buildSAMLTokenRequestXML($public_cert, $this->getPrivateKey(), $cvr, $appliesTo);

    $responseSecurityTokenService = SoapClient::doSOAP($endpointSecurityTokenService, $xml);
=======
    $appliesTo = 'http://stoettesystemerne.dk/service/organisation/3';
    // Aarhus kommune cvr.
    $cvr = '55133018';

    // Endpoint.
    $endpointSecurityTokenService = 'https://adgangsstyring.eksterntest-stoettesystemerne.dk/runtime/services/kombittrust/14/certificatemixed';

=======
    // Organisation config
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
    $orgConfig = $this->config->get('os2forms_organisation');

    $endpointSecurityTokenService = $orgConfig->get('security_token_service_endpoint');
    $appliesTo = $orgConfig->get('security_token_service_applies_to');
    $cvr = $orgConfig->get('cvr');
    $public_cert = file_get_contents(DRUPAL_ROOT . $orgConfig->get('public_cert_location'));

<<<<<<< HEAD
    $requestSecurityTokenService = $this->securityTokenService->getRequestSecurityTokenXML($endpointSecurityTokenService, $appliesTo, $cvr, self::ISSUER, $public_cert);
    $requestSecurityTokenServiceSigned = $this->securityTokenService->signRequestSecurityToken($requestSecurityTokenService, $this->getPrivateKey());
    $responseSecurityTokenService = SoapClient::doSOAP($endpointSecurityTokenService, $requestSecurityTokenServiceSigned);
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
    $xml = $this->securityTokenService->buildSAMLTokenRequestXML($public_cert, $this->getPrivateKey(), $cvr, $appliesTo);

    $responseSecurityTokenService = SoapClient::doSOAP($endpointSecurityTokenService, $xml);
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

    // Parse the RSTR that is returned.
    [$domSecurityTokenService, $xpath, $token] = $this->securityTokenService->parseRequestSecurityTokenResponse($responseSecurityTokenService);

    [$domSecurityTokenService, $token] = $this->securityTokenService->getDecrypted($domSecurityTokenService, $xpath, $token, $this->getPrivateKey());

<<<<<<< HEAD
<<<<<<< HEAD
    return $token != NULL ? $domSecurityTokenService->saveXML($token) : NULL;
=======
    if ($token != NULL) {
      return $domSecurityTokenService->saveXML($token);
    }

    return NULL;
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
    return $token != NULL ? $domSecurityTokenService->saveXML($token) : NULL;
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
  }

  /**
   * Gets certificate private key.
   */
  private function getPrivateKey() {
    $orgConfig = $this->config->get('os2forms_organisation');

<<<<<<< HEAD
<<<<<<< HEAD
    return file_get_contents(DRUPAL_ROOT . $orgConfig->get('priv_key_location'));
=======
    return file_get_contents(__DIR__ . '/../../' . $orgConfig->get('priv_key_location'));
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
    return file_get_contents(DRUPAL_ROOT . $orgConfig->get('priv_key_location'));
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
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
<<<<<<< HEAD
=======
   * Fetches organisation enhed level 2 from SF1500.
   */
  public function getOrganisationEnhedNiveauTo() {
    $token = $this->fetchSAMLToken();

    if ($token === NULL) {
      return 'Something went wrong collecting token';
    }

    // Mit org bruger id.
    $orgBrugerId = $this->getCurrentUserOrganisationId();

    if ($orgBrugerId === NULL) {
      return 'Something went wrong collecting organisation user id';
    }

    $responseArray = $this->organisationFunktionSoeg($orgBrugerId, NULL, $token);

    $id = $responseArray['ns3SoegOutput']['ns2IdListe']['ns2UUIDIdentifikator'];

    if (is_array($id)) {
      // @todo HANDLE PEOPLE WITH MORE THAN ONE FUNKTION?
      $id = $id[0];
    }

    if ($id === NULL) {
      return 'Something went wrong';
    }

    $responseArray = $this->organisationFunktionLaes($id, $token);

    // Level 1.
    $orgEnhedId = $responseArray['ns3LaesOutput']['ns3FiltreretOejebliksbillede']['ns3Registrering']['ns3RelationListe']['ns2TilknyttedeEnheder']['ns2ReferenceID']['ns2UUIDIdentifikator'];
    $responseArray = $this->organisationEnhedLaes($orgEnhedId, $token);

    // Level 2.
    $orgEnhedId = $responseArray['ns3LaesOutput']['ns3FiltreretOejebliksbillede']['ns3Registrering']['ns3RelationListe']['ns2Overordnet']['ns2ReferenceID']['ns2UUIDIdentifikator'];
    $responseArray = $this->organisationEnhedLaes($orgEnhedId, $token);

    return $responseArray['ns3LaesOutput']['ns3FiltreretOejebliksbillede']['ns3Registrering']['ns3AttributListe']['ns3Egenskab']['ns2EnhedNavn'];
  }

  /**
>>>>>>> 2552f8f (DW-454: Organisationsdata)
   * Fetches current user organisation user id.
   */
  private function getCurrentUserOrganisationId() {
    $user = $this->entityTypeManager->getStorage('user')->load($this->account->id());

<<<<<<< HEAD
<<<<<<< HEAD
    return $user->hasField('field_organisation_user_id') ? $user->get('field_organisation_user_id')->value : NULL;
=======
    if ($user->hasField('field_organisation_user_id')) {
      return $user->get('field_organisation_user_id')->value;
    }
    else {
      return NULL;
    }
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
    return $user->hasField('field_organisation_user_id') ? $user->get('field_organisation_user_id')->value : NULL;
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
  }

  /**
   * Performs bruger laes action.
   */
<<<<<<< HEAD
  private function brugerLaes($brugerId, $token) {
<<<<<<< HEAD
    $body = $this->organisationService->buildBodyBrugerLaesXML($brugerId);
=======
    $body = $this->organisationService->getBodyBrugerLaes($brugerId);
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
  private function brugerLaes($brugerId, $token)
  {
    $body = $this->organisationService->buildBodyBrugerLaesXML($brugerId);
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/bruger/6/';
    $action = 'http://kombit.dk/sts/organisation/bruger/laes';

<<<<<<< HEAD
<<<<<<< HEAD
    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);
    $request = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());

    $response = SoapClient::doSOAP($endpoint, $request, $action);
=======
    $header = $this->organisationService->getHeader($endpoint, $action, $token);
=======
    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

    $request = $this->createXMLRequest($header, $body);
    $request = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());

<<<<<<< HEAD
    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
    $response = SoapClient::doSOAP($endpoint, $request, $action);
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

    return $this->responseXMLToArray($response);
  }

  /**
   * Performs adresse laes action.
   */
  private function adresseLaes($adresseID, string $token) {
    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/adresse/6/';
    $action = 'http://kombit.dk/sts/organisation/adresse/laes';

<<<<<<< HEAD
<<<<<<< HEAD
    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);
    $body = $this->organisationService->buildBodyAdresseLaesXML($adresseID);
    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());
=======
    $header = $this->organisationService->getHeader($endpoint, $action, $token);
    $body = $this->organisationService->getBodyAdresseLaes($adresseID);
    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->getRequestSigned($request, $this->getPrivateKey());
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);
    $body = $this->organisationService->buildBodyAdresseLaesXML($adresseID);
    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);

    return $this->responseXMLToArray($response);
  }

  /**
   * Performs organisation enhed laes action.
   */
  private function organisationEnhedLaes($orgEnhedId, string $token) {
    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/organisationenhed/6/';
    $action = 'http://kombit.dk/sts/organisation/organisationenhed/laes';

<<<<<<< HEAD
<<<<<<< HEAD
    $body = $this->organisationService->buildBodyOrganisationEnhedLaesXML($orgEnhedId);
    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());
=======
    $body = $this->organisationService->getBodyOrganisationEnhedLaes($orgEnhedId);
    $header = $this->organisationService->getHeader($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->getRequestSigned($request, $this->getPrivateKey());
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
    $body = $this->organisationService->buildBodyOrganisationEnhedLaesXML($orgEnhedId);
    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);

    return $this->responseXMLToArray($response);
  }

  /**
   * Performs organisation funktion laes action.
   */
  private function organisationFunktionLaes($orgFunktionId, string $token) {
    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/organisationfunktion/6/';
    $action = 'http://kombit.dk/sts/organisation/organisationfunktion/laes';

<<<<<<< HEAD
<<<<<<< HEAD
    $body = $this->organisationService->buildBodyOrganisationFunktionLaesXML($orgFunktionId);
    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());
=======
    $body = $this->organisationService->getBodyOrganisationFunktionLaes($orgFunktionId);
    $header = $this->organisationService->getHeader($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->getRequestSigned($request, $this->getPrivateKey());
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
    $body = $this->organisationService->buildBodyOrganisationFunktionLaesXML($orgFunktionId);
    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);

    return $this->responseXMLToArray($response);
  }

  /**
   * Performs organisation funktion soeg action.
   */
  private function organisationFunktionSoeg($orgBrugerId, $funktionsNavn, string $token) {
    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/organisationfunktion/6/';
    $action = 'http://kombit.dk/sts/organisation/organisationfunktion/soeg';

<<<<<<< HEAD
<<<<<<< HEAD
    $body = $this->organisationService->buildBodyOrganisationFunktionSoegXML($orgBrugerId, $funktionsNavn, NULL);
    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());
=======
    $body = $this->organisationService->getBodyOrganisationFunktionSoeg($orgBrugerId, $funktionsNavn);
    $header = $this->organisationService->getHeader($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->getRequestSigned($request, $this->getPrivateKey());
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
    $body = $this->organisationService->buildBodyOrganisationFunktionSoegXML($orgBrugerId, $funktionsNavn, null);
    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);

    return $this->responseXMLToArray($response);
  }

  /**
   * Performs person laes action.
   */
  private function personLaes($personId, string $token) {
    $endpoint = 'https://organisation.eksterntest-stoettesystemerne.dk/organisation/person/6/';
    $action = 'http://kombit.dk/sts/organisation/person/laes';

<<<<<<< HEAD
<<<<<<< HEAD
    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);
    $body = $this->organisationService->buildBodyPersonLaesXML($personId);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());
=======
    $header = $this->organisationService->getHeader($endpoint, $action, $token);
    $body = $this->organisationService->getBodyPersonLaes($personId);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->getRequestSigned($request, $this->getPrivateKey());
>>>>>>> 2552f8f (DW-454: Organisationsdata)
=======
    $header = $this->organisationService->buildHeaderXML($endpoint, $action, $token);
    $body = $this->organisationService->buildBodyPersonLaesXML($personId);

    $request = $this->createXMLRequest($header, $body);

    $requestSigned = $this->organisationService->buildSignedRequest($request, $this->getPrivateKey());
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
    $response = SoapClient::doSOAP($endpoint, $requestSigned, $action);

    return $this->responseXMLToArray($response);
  }

<<<<<<< HEAD
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

<<<<<<< HEAD
    $adresser = $this->getValue($data, $adresseKeys);
=======
    $adresser = $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($adresseKeys));
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

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
<<<<<<< HEAD
      if ($this->getValue($adresse, $adresseRolleLabelKeys) === $attribute) {

        $adresseId = $this->getValue($adresse, $adresseReferenceUuidKeys);
=======
      if ($this->propertyAccessor->getValue($adresse, $this->convertKeysToPropertyAccessorFormat($adresseRolleLabelKeys)) === $attribute) {

        $adresseId = $this->propertyAccessor->getValue($adresse, $this->convertKeysToPropertyAccessorFormat($adresseReferenceUuidKeys));
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)

        if (NULL === $adresseId) {
          continue;
        }

        $data = $this->adresseLaes($adresseId, $token);
<<<<<<< HEAD

        return $this->getValue($data, $adresseTekstKeys, '');
=======
        return $this->propertyAccessor->getValue($data, $this->convertKeysToPropertyAccessorFormat($adresseTekstKeys)) ?: '';
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
      }
    }

    return '';
  }

  /**
<<<<<<< HEAD
   * Gets value from data according to keys.
   */
  private function getValue($data, array $keys, $defaultValue = NULL) {

    // @see https://symfony.com/doc/current/components/property_access.html#reading-from-arrays
    $propertyPath = '[' . implode('][', $keys) . ']';

    if ($this->propertyAccessor->isReadable($data, $propertyPath)) {
      return $this->propertyAccessor->getValue($data, $propertyPath) ?: $defaultValue;
    }

    return $defaultValue;
=======
   * Converts an array of keys into Symfony PropertyAccessor property path format.
   * @see https://symfony.com/doc/current/components/property_access.html#reading-from-arrays
   *
   * @example
   * $keys = [
   *   'some_special_key',
   *   'some_other_special_key'
   * ];
   *
   * convertKeysToPropertyAccessorFormat($keys) = '[some_special_key][some_other_special_key'
   */
  private function convertKeysToPropertyAccessorFormat(array $keys): string {
    $value = '';

    foreach ($keys as $key) {
      $value .= '[' . $key . ']';
    }

    return $value;
>>>>>>> 39a70a9 (DW-545: Refactoring and clean up)
  }

=======
>>>>>>> 2552f8f (DW-454: Organisationsdata)
}
