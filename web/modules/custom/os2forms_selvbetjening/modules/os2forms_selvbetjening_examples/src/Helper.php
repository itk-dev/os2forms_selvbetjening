<?php

namespace Drupal\os2forms_selvbetjening_examples;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeStorageInterface;

/**
 * A helper class.
 */
class Helper {
  /**
   * Node form ids.
   *
   * @var array|string[]
   */
  private array $nodeFormIds = [
    'example_flow_step_2',
  ];

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  private NodeStorageInterface $nodeStorage;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $configFactory;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->configFactory = $configFactory;
  }

  /**
   * Implements hook_install().
   */
  public function install() {
    // hook_install() semms to be called twice (!) so we clean up before
    // creating nodes.
    $this->deleteFormNodes();
    $this->createFormNodes();
  }

  /**
   * Implements hook_uninstall().
   */
  public function uninstall() {
    $this->deleteFormNodes();
  }

  /**
   * Create form nodes.
   */
  private function createFormNodes() {
    foreach ($this->nodeFormIds as $formId) {
      $node = Node::create([
        'type' => 'webform',
        'title' => sprintf('Webform node for form %s', $formId),
        'webform' => [
          'target_id' => $formId,
        ],
        'status' => Node::PUBLISHED,
        'moderation_state' => 'published',
      ]);
      $node->save();

      if ('example_flow_step_2' === $formId) {
        $config = $this->configFactory->getEditable('maestro.maestro_template.example_flow_page');
        $config->set('tasks.example_flow_step_2.data.webform_nodes_attached_to', 'node/' . $node->id());
        $config->save();
      }
    }
  }

  /**
   * Delete form nodes.
   */
  private function deleteFormNodes() {
    $nodeIds = $this->nodeStorage
      ->getQuery()
      ->condition('webform', $this->nodeFormIds)
      ->accessCheck(FALSE)
      ->execute();
    $nodes = $this->nodeStorage->loadMultiple($nodeIds);
    foreach ($nodes as $node) {
      $node->delete();
    }
  }

}
