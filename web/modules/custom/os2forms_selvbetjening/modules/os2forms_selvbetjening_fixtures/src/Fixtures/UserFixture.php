<?php

namespace Drupal\os2forms_selvbetjening_fixtures\Fixtures;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\content_fixtures\Fixture\DependentFixtureInterface;
use Drupal\permissions_by_term\Service\AccessStorage;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * User fixture.
 */
final class UserFixture extends AbstractFixture implements DependentFixtureInterface {
  use AutowireTrait;

  const string GROUP = 'user';

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  private UserStorageInterface $userStorage;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'permissions_by_term.access_storage')]
    private readonly AccessStorage $accessStorage,
  ) {
    $this->userStorage = $entityTypeManager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public function load(): void {
    for ($i = 0; $i < 10; $i++) {
      $name = sprintf('forloeb_designer%02d', $i);
      $mail = $name . '@example.com';
      $password = 'password';
      if ($user = $this->userStorage->loadByProperties(['name' => $name])) {
        $this->userStorage->delete([$user]);
      }

      $user = $this
        ->userStorage->create([])
        ->setUsername($name)
        ->setEmail($mail)
        ->setPassword($password)
        ->addRole('forloeb_designer')
        ->activate();
      $user->save();
      $this->setReference('user:' . $name, $user);

      $langcode = $user->getPreferredLangcode();
      $departmentIds = array_map(static fn(TermInterface $term) => $term->id(), [
        $this->getReference('user_affiliation:Department 1'),
        $this->getReference('user_affiliation:Department 3'),
      ]);
      foreach ($departmentIds as $departmentId) {
        $this->accessStorage->addTermPermissionsByUserIds([$user->id()], $departmentId, $langcode);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return [
      UserAffiliationFixture::class,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getFixtureGroups(): array {
    return [
      static::GROUP,
      ...WebformFixture::getFixtureGroups(),
    ];
  }

}
