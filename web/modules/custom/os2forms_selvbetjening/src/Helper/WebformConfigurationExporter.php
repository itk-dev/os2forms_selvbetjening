<?php

namespace Drupal\os2forms_selvbetjening\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\os2forms_selvbetjening\Exception\PermissionException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\CsvEncoder;

/**
 * The WebformConfigurationExporter class.
 */
class WebformConfigurationExporter {

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user.
   */
  public function __construct(private readonly ConfigFactoryInterface $configFactory, private readonly AccountInterface $account) {
  }

  /**
   * Extracts csv file containing webform configuration.
   *
   * @param string $filename
   *   Filename for csv file.
   *
   * @throws \Drupal\os2forms_selvbetjening\Exception\PermissionException
   *   Permission exception.
   */
  public function extractWebformConfiguration(string $filename): void {
    if (!$this->account->hasPermission('access webform configuration export')) {
      throw new PermissionException('Permission to webform configuration export denied.');
    }

    // This is just config names so fetching all should be fine.
    $webformConfigNames = $this->configFactory->listAll('webform.webform.');

    $data = [];
    foreach ($webformConfigNames as $webformConfigName) {
      $webformConfig = $this->configFactory->get($webformConfigName);

      $id = $webformConfig->get('id') ?? '';
      $title = $webformConfig->get('title') ?? '';
      $author = $webformConfig->get('uid') ?? '';
      $category = $webformConfig->get('category') ?? '';
      $purgeDays =
        is_array($webformConfig->get('settings')) && isset($webformConfig->get('settings')['purge_days']) ?
          $webformConfig->get('settings')['purge_days']
          : '';
      $archived = $webformConfig->get('archive') ?? '';
      $template = $webformConfig->get('template') ?? '';

      $data[] = [
        'id' => $id,
        'title' => $title,
        'author' => $author,
        'category' => $category,
        'purge_days' => $purgeDays,
        'archived' => $archived,
        'template' => $template,
      ];
    }

    $contentType = 'ext/csv; charset=utf-8';
    $csvEncoder = new CsvEncoder();
    $content = $csvEncoder->encode($data, CsvEncoder::FORMAT);

    $response = new Response($content, Response::HTTP_OK, [
      'content-type' => $contentType,
      'content-disposition' => HeaderUtils::makeDisposition(
        HeaderUtils::DISPOSITION_ATTACHMENT,
        $filename
      ),
    ]);

    $response->send();
  }

}
