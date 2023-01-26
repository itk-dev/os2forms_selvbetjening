<?php

namespace Drupal\os2forms_organisation\Helper;

use Drupal\os2forms_organisation\Form\SettingsForm;
use ItkDev\Serviceplatformen\Service\SF1500\SF1500;
use ItkDev\Serviceplatformen\Service\SF1500\SF1500XMLBuilder;
use ItkDev\Serviceplatformen\Service\SF1514\SF1514;
use ItkDev\Serviceplatformen\Service\SoapClient;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Organisation Helper service.
 */
class OrganisationHelper {
  /**
   * The PropertyAccessor.
   *
   * @var \Symfony\Component\PropertyAccess\PropertyAccessor
   */
  private PropertyAccessor $propertyAccessor;

  /**
   * The Settings.
   *
   * @var \Drupal\os2forms_organisation\Helper\SettingsInterface|Settings
   */
  private SettingsInterface $settings;

  /**
   * The Certificate locator.
   *
   * @var \Drupal\os2forms_organisation\Helper\CertificateLocatorHelper
   */
  private CertificateLocatorHelper $certificateLocator;

  /**
   * The SF1500 service.
   *
   * @var \ItkDev\Serviceplatformen\Service\SF1500\SF1500
   */
  private ?SF1500 $sf1500 = NULL;

  /**
   * Constructor.
   */
  public function __construct(PropertyAccessor $propertyAccessor, CertificateLocatorHelper $certificateLocator, Settings $settings) {
    $this->propertyAccessor = $propertyAccessor;
    $this->certificateLocator = $certificateLocator;
    $this->settings = $settings;
  }

  /**
   * Gets SF1500 Service.
   */
  // phpcs:ignore
  private function getSF1500(): SF1500 {
    if (NULL === $this->sf1500) {

      $soapClient = new SoapClient([
        'cache_expiration_time' => $this->settings->get(SettingsForm::CACHE_EXPIRATION),
      ]);

      $options = [
        'certificate_locator' => $this->certificateLocator->getCertificateLocator(),
        'authority_cvr' => $this->settings->get(SettingsForm::AUTHORITY_CVR),
        'sts_applies_to' => $this->settings->get(SettingsForm::ORGANISATION_SERVICE_ENDPOINT_REFERENCE),
        'test_mode' => $this->settings->get(SettingsForm::TEST_MODE),
      ];

      $sf1514 = new SF1514($soapClient, $options);

      $sf1500XMLBuilder = new SF1500XMLBuilder();

      $this->sf1500 = new SF1500($soapClient, $sf1514, $sf1500XMLBuilder, $this->propertyAccessor, $options);
    }

    return $this->sf1500;
  }

  /**
   * Gets Person name from bruger id.
   */
  public function getPersonName(string $brugerId) {
    return $this->getSF1500()->getPersonName($brugerId);
  }

  /**
   * Gets Person Email from bruger id.
   */
  public function getPersonEmail(string $brugerId) {
    return $this->getSF1500()->getPersonEmail($brugerId);
  }

  /**
   * Gets Person AZ ident from bruger id.
   */
  // phpcs:ignore
  public function getPersonAZIdent(string $brugerId) {
    return $this->getSF1500()->getPersonAZIdent($brugerId);
  }

  /**
   * Gets Person Phone from bruger id.
   */
  public function getPersonPhone(string $brugerId) {
    return $this->getSF1500()->getPersonPhone($brugerId);
  }

  /**
   * Gets Person Location from bruger id.
   */
  public function getPersonLocation(string $brugerId) {
    return $this->getSF1500()->getPersonLocation($brugerId);
  }

  /**
   * Gets Organisation Funktioner from bruger id.
   */
  public function getOrganisationFunktioner(string $brugerId) {
    return $this->getSF1500()->getOrganisationFunktioner($brugerId);
  }

  /**
   * Gets Organisation Funktionsnavn from funktions id.
   */
  public function getFunktionsNavn($funktionsId) {
    return $this->getSF1500()->getFunktionsNavn($funktionsId);
  }

  /**
   * Gets Organisation Endhed from funktions id.
   */
  public function getOrganisationEnhed($funktionsId) {
    return $this->getSF1500()->getOrganisationEnhed($funktionsId);
  }

  /**
   * Gets Organisation Address from funktions id.
   */
  public function getOrganisationAddress($funktionsId) {
    return $this->getSF1500()->getOrganisationAddress($funktionsId);
  }

  /**
   * Gets Organisation Enhed Niveau To from funktions id.
   */
  public function getOrganisationEnhedNiveauTo($funktionsId) {
    return $this->getSF1500()->getOrganisationEnhedNiveauTo($funktionsId);
  }

  /**
   * Gets Person Magistrat from funktions id..
   */
  public function getPersonMagistrat($funktionsId) {
    return $this->getSF1500()->getPersonMagistrat($funktionsId);
  }

}
