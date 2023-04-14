<?php

namespace Drupal\os2forms_fbs_handler\Client\Model;

/**
 * @class
 * Wrapper class to represent and patron.
 */
final class Patron {

  public function __construct(
    public readonly ?string $patronId = NULL,
    public readonly ?bool $receiveSms = FALSE,
    public readonly ?bool $receivePostalMail = FALSE,
    public readonly ?array $notificationProtocols = NULL,
    public readonly ?string $phoneNumber = NULL,
    public readonly ?string $onHold = NULL,
    public readonly ?string $preferredLanguage = NULL,
    public readonly ?string $guardianVisibility = NULL,
    // Allow these properties below to be updatable.
    public ?string $emailAddress = NULL,
    public ?bool $receiveEmail = NULL,
    public ?string $preferredPickupBranch = NULL,
    public ?string $cpr = NULL,
    public ?string $pincode = NULL,
  ) {
  }

  public function toArray(): array {
    return [
      'receiveEmail' => $this->receiveEmail,
      'receiveSms' => $this->receiveSms,
      'receivePostalMail' => $this->receivePostalMail,
      'emailAddress' => $this->emailAddress,
      'notificationProtocols' => $this->notificationProtocols,
      'phoneNumber' => $this->phoneNumber,
      'preferredPickupBranch' => $this->preferredPickupBranch,
      'onHold' => $this->onHold,
      'preferredLanguage' => $this->preferredLanguage,
      'guardianVisibility' => $this->guardianVisibility,
    ];
  }

}

