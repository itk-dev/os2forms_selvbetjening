<?php

namespace Drupal\os2forms_selvbetjening_fixtures\Fixtures;

use Drupal\content_fixtures\Fixture\AbstractFixture as BaseAbstractFixture;
use Drupal\content_fixtures\Fixture\FixtureGroupInterface;

/**
 * Abstract fixture.
 */
abstract class AbstractFixture extends BaseAbstractFixture implements FixtureGroupInterface {

  /**
   * Get all groups that this fixture should be part of.
   *
   * This basically tells which groups must be loaded for the fixture to have
   * all dependencies in place. In other words, this should return something
   * that's basically the reverse of static::getDependencies().
   */
  abstract public static function getFixtureGroups(): array;

  /**
   * {@inheritdoc}
   */
  public function getGroups() {
    return static::getFixtureGroups();
  }

}
