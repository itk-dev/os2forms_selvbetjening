<?php

namespace Drupal\os2forms_selvbetjening_fixtures\Fixtures;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\content_fixtures\Fixture\DependentFixtureInterface;
use Drupal\content_fixtures\Fixture\FixtureGroupInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\webform\WebformEntityStorageInterface;

/**
 * Webform fixture.
 */
final class WebformFixture extends AbstractFixture implements DependentFixtureInterface, FixtureGroupInterface {
  use AutowireTrait;

  const string GROUP = 'webform';

  /**
   * The webform storage.
   *
   * @var \Drupal\webform\WebformEntityStorageInterface
   */
  private WebformEntityStorageInterface $webformStorage;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->webformStorage = $entityTypeManager->getStorage('webform');
  }

  /**
   * {@inheritdoc}
   */
  public function load(): void {
    $id = 'fixture_test';
    if ($webform = $this->webformStorage->load($id)) {
      $this->webformStorage->delete([$webform]);
    }

    $departmentIds = array_map(static fn(TermInterface $term) => $term->id(), [
      $this->getReference('user_affiliation:Department 1'),
      $this->getReference('user_affiliation:Department 3'),
    ]);

    /** @var \Drupal\user\UserInterface $owner */
    $owner = $this->getReference('user:forloeb_designer01');
    $webform = $this
      ->webformStorage->create([
        'id' => $id,
        'title' => $id,
      ])
      ->setOwner($owner)
      ->setElements([
        'name' => [
          '#type' => 'textfield',
          '#title' => 'Name',
        ],
      ])
      ->setThirdPartySetting('os2forms_permissions_by_term', 'settings', array_combine($departmentIds, $departmentIds))
      ->setThirdPartySetting('webform_encrypt', 'element', [
        'name' => [
          'encrypt' => TRUE,
          'encrypt_profile' => 'webform',
        ],
      ]);

    // phpcs:disable
    // @todo We need to set some webforms revisions stuff to not get the warning
    //   Can only flip string and integer values, entry skipped ContentEntityStorageBase.php:667
    // $webform->setThirdPartySetting('webform_revisions', 'contentEntity_id', …)
    // phpcs:enable
    $webform->save();
    $this->setReference('webform:' . $id, $webform);

    $id = 'fixture_test_file_upload';
    if ($webform = $this->webformStorage->load($id)) {
      $this->webformStorage->delete([$webform]);
    }

    $webform = $this
      ->webformStorage->create([
        'id' => $id,
        'title' => $id,
      ])
      ->setOwner($owner)
      ->setElements([
        'text_file' => [
          '#type' => 'managed_file',
          '#title' => 'text file',
          '#file_extensions' => 'txt png',
        ],
      ])
      ->setThirdPartySetting('os2forms_permissions_by_term', 'settings', array_combine($departmentIds, $departmentIds));
    $webform->save();
    $this->setReference('webform:' . $id, $webform);
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return [
      UserAffiliationFixture::class,
      UserFixture::class,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getFixtureGroups(): array {
    return [
      static::GROUP,
      ...WebformSubmissionFixture::getFixtureGroups(),
    ];
  }

}
