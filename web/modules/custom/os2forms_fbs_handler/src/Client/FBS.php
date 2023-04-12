<?php

namespace Drupal\os2forms_fbs_handler\Client\FBS;

use Drupal\Core\Config\Config;
use GuzzleHttp\Client;

final class FBS {

  public function __construct(
    private readonly Client $client,
    private readonly array $configuraion,
  ) {

  }

  public function authenticate() {
    $this->client->request('POST', 'https://xxx');
  }

}

