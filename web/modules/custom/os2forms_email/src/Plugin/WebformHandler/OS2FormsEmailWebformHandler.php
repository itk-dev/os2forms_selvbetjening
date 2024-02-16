<?php

namespace Drupal\os2forms_email\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Emails a webform submission.
 *
 * @WebformHandler(
 *   id = "email",
 *   label = @Translation("OS2Forms email"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends a webform submission via an email."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   tokens = TRUE,
 * )
 */
class OS2FormsEmailWebformHandler extends EmailWebformHandler {

  /**
   * Adds extra check to to_mail before sending message.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $message
   *   An array of message parameters.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function sendMessage(WebformSubmissionInterface $webform_submission, array $message) {

    if (!$this->configFactory->get('os2forms_email')->get('pattern_enable')) {
      return parent::sendMessage($webform_submission, $message);
    }

    $pattern = $this->configFactory->get('os2forms_email')->get('pattern');
    $emailAddresses = explode(',', $message['to_mail']);

    $validEmails = [];

    foreach ($emailAddresses as $emailAddress) {
      if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL) || !preg_match($pattern, $emailAddress)) {

        $context = [
          '@form' => $this->getWebform()->label(),
          '@handler' => $this->label(),
          '@email' => $emailAddress,
          'link' => ($webform_submission->id()) ? $webform_submission->toLink($this->t('View'))->toString() : NULL,
          'webform_submission' => $webform_submission,
          'handler_id' => $this->getHandlerId(),
          'operation' => 'failed sending email',
        ];

        if ($webform_submission->getWebform()->hasSubmissionLog()) {
          // Log detailed message to the 'webform_submission' log.
          $this->getLogger('webform_submission')->notice("Email not sent for '@handler' handler because the email (@email) is not valid.", $context);
        }

        $this->sendMessageToWebformAuthor($webform_submission, $message, $context);

      }
      else {
        $validEmails[] = $emailAddress;
      }

    }

    if (!empty($validEmails)) {
      $message['to_mail'] = implode(',', $validEmails);

      return parent::sendMessage($webform_submission, $message);
    }

    return FALSE;

  }

  /**
   * Sends message to webform author.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $message
   *   An array of message parameters.
   * @param array $context
   *   An array with context.
   */
  private function sendMessageToWebformAuthor(WebformSubmissionInterface $webform_submission, array $message, array $context): void {

    if (!$this->configFactory->get('os2forms_email')->get('error_message_enable')) {
      return;
    }

    $pattern = $this->configFactory->get('os2forms_email')->get('pattern');
    $authorEmail = $webform_submission->getWebform()->getOwner()->getEmail();

    if (!filter_var($authorEmail, FILTER_VALIDATE_EMAIL) || !preg_match($pattern, $authorEmail)) {
      // Cannot send email to author email. Log it and give up.
      if ($webform_submission->getWebform()->hasSubmissionLog()) {

        $authorMessageContext = $context;
        $authorMessageContext['@email'] = $authorEmail;

        $this->getLogger('webform_submission')->notice("Email not sent for '@handler' handler because the email (@email) is not valid.", $authorMessageContext);
      }

      return;
    }

    $errorMessage = $this->defaultConfiguration();

    $errorMessage['to_mail'] = $authorEmail;
    $errorMessage['subject'] = $this->configFactory->get('os2forms_email')->get('error_message_subject');

    $body = sprintf(
      "Email not sent for handler (%s) on formular (%s) because the email (%s) is not valid.",
      $context['@handler'],
      $context['@form'],
      $context['@email']
    );

    $errorMessage['body'] = sprintf('<p>Kære %s</p><p>%s</p>', $webform_submission->getWebform()->getOwner()->getDisplayName(), $body);
    $errorMessage['from_mail'] = $this->configFactory->get('os2forms_email')->get('error_message_from_email');
    $errorMessage['from_name'] = $this->configFactory->get('os2forms_email')->get('error_message_from_name');
    $errorMessage['webform_submission'] = $message['webform_submission'];
    $errorMessage['handler'] = $message['handler'];

    parent::sendMessage($webform_submission, $errorMessage);
  }

}
