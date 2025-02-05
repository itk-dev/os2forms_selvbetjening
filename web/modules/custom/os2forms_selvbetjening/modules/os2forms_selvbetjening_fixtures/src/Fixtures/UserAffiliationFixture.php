<?php

namespace Drupal\os2forms_selvbetjening_fixtures\Fixtures;

use Drupal\content_fixtures\Fixture\FixtureGroupInterface;

/**
 * User affiliation fixtures.
 */
final class UserAffiliationFixture extends AbstractTaxonomyTermFixture implements FixtureGroupInterface {

  /**
   * {@inheritdoc}
   */
  protected static string $vocabularyId = 'user_affiliation';

  /**
   * {@inheritdoc}
   */
  protected static array $terms = [
    'Department 1',
    'Department 2',
    'Department 3',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getFixtureGroups(): array {
    return [
      ...UserFixture::getFixtureGroups(),
      ...WebformFixture::getFixtureGroups(),
    ];
  }

}
