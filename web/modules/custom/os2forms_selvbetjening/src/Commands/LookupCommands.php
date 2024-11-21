<?php

namespace Drupal\os2forms_selvbetjening\Commands;

use Drupal\os2web_datalookup\Plugin\DataLookupManager;
use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupCprInterface;
use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DatafordelerCVR;
use Drush\Commands\DrushCommands;
use Symfony\Component\Yaml\Yaml;

/**
 * Lookup commands.
 */
class LookupCommands extends DrushCommands {

  /**
   * Constructor.
   */
  public function __construct(
    private readonly DataLookupManager $dataLookupManager,
  ) {
  }

  /**
   * Look up CPR.
   *
   * @param string $cpr
   *   The cpr.
   * @param array $options
   *   The command options.
   *
   * @command os2forms-selvbetjening:look-up:cpr
   * @usage os2forms-selvbetjening:look-up:cpr --help
   */
  public function lookUpCpr(
    string $cpr,
    array $options = [
      'dump-configuration' => FALSE,
    ],
  ) {
    try {
      $instance = $this->dataLookupManager->createDefaultInstanceByGroup('cpr_lookup');
      assert($instance instanceof DataLookupCprInterface);

      if ($options['dump-configuration']) {
        $this->output()->writeln([
          Yaml::dump($instance->getConfiguration()),
        ]);
      }
      $result = $instance->lookup($cpr);

      if (!$result->isSuccessful()) {
        $this->output()->writeln($result->getErrorMessage());
      }
      else {
        $this->output()->write($result->getName());
      }
    }
    catch (\Exception $exception) {
      $this->output()->writeln($exception->getMessage());
    }
  }

  /**
   * Look up CVR.
   *
   * @param string $cvr
   *   The cvr.
   * @param array $options
   *   The command options.
   *
   * @command os2forms-selvbetjening:look-up:cvr
   * @usage os2forms-selvbetjening:look-up:cvr --help
   */
  public function lookUpCvr(
    string $cvr,
    array $options = [
      'dump-configuration' => FALSE,
    ],
  ) {
    try {
      $instance = $this->dataLookupManager->createDefaultInstanceByGroup('cvr_lookup');
      assert($instance instanceof DatafordelerCVR);

      if ($options['dump-configuration']) {
        $this->output()->writeln([
          Yaml::dump($instance->getConfiguration()),
        ]);
      }
      $result = $instance->lookup($cvr);

      if (!$result->isSuccessful()) {
        $this->output()->writeln($result->getErrorMessage());
      }
      else {
        $this->output()->write($result->getName());
      }
    }
    catch (\Exception $exception) {
      $this->output()->writeln($exception->getMessage());
    }
  }

}
