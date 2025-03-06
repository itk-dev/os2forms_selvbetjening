<?php

namespace Drupal\os2forms_queued_email\Drush\Commands;

use Drupal\Core\Mail\MailManager;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class Os2formsEmailHandlerCommands extends DrushCommands {

  /**
   * Constructs an Os2formsEmailHandlerCommands object.
   */
  public function __construct(
    private readonly MailManager $mailManager,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail'),
    );
  }

  /**
   * Command description here.
   *
   * @phpstan-param array<string, mixed> $options
   */
  #[CLI\Command(name: 'os2forms_email_handler:test-email')]
  #[CLI\Usage(name: 'os2forms_email_handler:test-email --recipient=jekua@aarhus.dk --message-subject="Some exiting subject"', description: 'Send email to a specific recipient.')]
  public function commandName(
    $options = [
      'recipient' => 'test@example.com',
      'module' => 'webform',
      'module-key' => 'webform_key',
      'message-body' => 'Test email sent from drush.',
      'message-subject' => 'Test email',
    ],
  ) {

    $message = [
      'body' => $options['message-body'],
      'subject' => $options['message-subject'],
    ];
    $this->mailManager->mail($options['module'], $options['module-key'], $options['recipient'], 'da', $message);
    $this->logger()->success(sprintf('Sending email to %s', $options['recipient']));
  }

}
