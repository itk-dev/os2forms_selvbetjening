<?php

namespace Drupal\os2forms_queue_submission_relation\EventSubscriber;

use Drupal\advancedqueue\Event\AdvancedQueueEvents;
use Drupal\advancedqueue\Event\JobEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\os2forms_queue_submission_relation\Helper\Helper;

/**
 * Class UserLoginSubscriber.
 *
 * @package Drupal\custom_events\EventSubscriber
 */
class AdvancedQueueProcessSubscriber implements EventSubscriberInterface {

  /**
   * The os2forms_queue_submission_relation helper.
   *
   * @var \Drupal\os2forms_queue_submission_relation\Helper\Helper
   */
  protected Helper $helper;

  /**
   * The AdvancedQueueProcessSubscriber constructor.
   *
   * @param Helper $helper
   */
  public function __construct(Helper $helper) {
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      AdvancedQueueEvents::PRE_PROCESS => 'onQueuePreProcess',
    ];
  }

  /**
   * Act when advanced queue runs its preprocess event.
   *
   * @param \Drupal\advancedqueue\Event\JobEvent $event
   *   The job that is about to be processed.
   *
   * @return void
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onQueuePreProcess(JobEvent $event) {
    $data = $this->helper->getDataFromPayload($event->getJob()->getPayload());
    $data['job_id'] = (int)$event->getJob()->getId();
    $this->helper->addUpdateRelation($data);
  }

}
