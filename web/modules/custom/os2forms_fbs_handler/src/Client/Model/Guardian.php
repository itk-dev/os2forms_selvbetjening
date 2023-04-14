<?php

namespace Drupal\os2forms_fbs_handler\Client\Model;

/**
 * @class
 * Wrapper class to represent and guardian.
 */
final class Guardian {

  public function __construct(
    protected readonly string $cpr,
    protected readonly string $name,
    protected readonly string $email,
  ) {
  }

  public function toArray(): array {
    return [
      'cprNumber' => $this->cpr,
      'name' => $this->name,
      'email' => $this->email,
    ];
  }

}

