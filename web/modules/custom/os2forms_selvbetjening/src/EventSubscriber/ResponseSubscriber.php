<?php

namespace Drupal\os2forms_selvbetjening\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Site\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response subscriber.
 */
class ResponseSubscriber implements EventSubscriberInterface {
  /**
   * The admin context.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  private AdminContext $adminContext;

  /**
   * Constructor.
   */
  public function __construct(AdminContext $adminContext) {
    $this->adminContext = $adminContext;
  }

  /**
   * Response event callback.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   */
  public function onResponse(ResponseEvent $event) {
    $response = $event->getResponse();
    $message = $this->getMessage();

    if (NULL !== $message &&  $response instanceof HtmlResponse) {
      $content = $response->getContent();
      $content = preg_replace('/<body[^>]*>/', '$0' . $message, $content);

      $response->setContent($content);
    }
  }

  /**
   * Get message.
   */
  private function getMessage(): ?string {
    if ($this->adminContext->isAdminRoute()) {
      $settings = Settings::get('os2forms_selvbetjening');
      $message = $settings['admin_message'] ?? NULL;
      $style = $settings['admin_message_style'] ?? 'background: orange; color: white';

      if (NULL !== $message) {
        return sprintf('<div style="%s">%s</div>', $style, $message);
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => ['onResponse', -1000],
    ];
  }

}
