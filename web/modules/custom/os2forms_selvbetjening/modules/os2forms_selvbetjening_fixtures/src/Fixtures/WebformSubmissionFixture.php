<?php

namespace Drupal\os2forms_selvbetjening_fixtures\Fixtures;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\content_fixtures\Fixture\DependentFixtureInterface;
use Drupal\content_fixtures\Fixture\FixtureGroupInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionStorageInterface;

/**
 * Webform fixture.
 */
final class WebformSubmissionFixture extends AbstractFixture implements DependentFixtureInterface, FixtureGroupInterface {
  use AutowireTrait;

  const string GROUP = 'webform_submission';

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  private WebformSubmissionStorageInterface $submissionStorage;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    private readonly FileSystemInterface $fileSystem,
    private readonly FileRepositoryInterface $fileRepository,
  ) {
    $this->submissionStorage = $entityTypeManager->getStorage('webform_submission');
  }

  /**
   * Get max number of submissions for a webform.
   */
  private function getMaxNumberOfSubmissions(int $number, WebformInterface $webform): int {
    $configValue = getenv('MAX_NUMBER_OF_SUBMISSIONS');
    // Check if the config looks like a JSON object.
    if (is_string($configValue) && str_starts_with($configValue, '{')) {
      $configValues = (array) json_decode($configValue);
    }
    else {
      $configValues = [$webform->id() => intval($configValue)];
    }
    $max = $configValues[$webform->id()] ?? 0;

    return $max > 0 ? min($max, $number) : $number;
  }

  /**
   * {@inheritdoc}
   */
  public function load(): void {

    $webformId = 'fixture_test';
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getReference('webform:' . $webformId);

    $numberOfSubmissions = $this->getMaxNumberOfSubmissions(500, $webform);
    for ($i = 0; $i < $numberOfSubmissions; $i++) {
      $submission = $this->submissionStorage->create([
        'webform' => $webform,
      ])
        ->setData(['name' => sprintf('name %03d', $i)]);
      $submission->save();
    }

    $webformId = 'fixture_test_file_upload';
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getReference('webform:' . $webformId);

    $numberOfSubmissions = $this->getMaxNumberOfSubmissions(500, $webform);
    for ($i = 0; $i < $numberOfSubmissions; $i++) {
      $path = __DIR__ . '/files/square_10_red.png';
      $data = file_get_contents($path);

      $destination = 'private://webform/' . $webform->id() . '/' . basename($path);
      $directory = dirname($destination);
      $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
      $file = $this->fileRepository->writeData($data, $destination);

      $submission = $this->submissionStorage->create([
        'webform' => $webform,
      ])
        ->setData(['text_file' => $file->id()]);
      $submission->save();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return [
      WebformFixture::class,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getFixtureGroups(): array {
    return [
      static::GROUP,
    ];
  }

}
