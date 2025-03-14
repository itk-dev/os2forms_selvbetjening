# OS2Forms Queued Email

Adds an mail plugin (`Queued SMTP PHP mailer`) for queueing webform emails.

## Usage

Go to `/admin/config/system/mailsystem` and configure the Webform module to
use `Queued SMTP PHP mailer` as Sender.

Make sure the `private://queued-email-files/` directory exists
and is writable. More information on this later.

## Details

**Note** that the `Queued SMTP PHP mailer` builds upon `SMTP PHP mailer` and
only overrides the sender. The formatting method remains the same.

To lighten payload data in the queued jobs several things are done:

Attachment elements `getEmailAttachments` methods are overridden to not pass
on `filecontent`. This is added back during the process of the job.

A copy of OS2Forms attachments is referenced and kept in the filesystem.
This not only lightens job payload but also ensures that the content is
consistent with submission data at sending time in case of any edits.
For this make sure that the `private://queued-email-files/` directory exists
and is writable.

## Queue

We use an [Advanced Queue](https://www.drupal.org/project/advancedqueue)
called `os2forms_queued_email`.

Process the queued email:

```shell
drush advancedqueue:queue:process os2forms_queued_email
```

or consider setting up a cronjob for this:

```shell
*/5 * * * * /path/to/drush advancedqueue:queue:process os2forms_queued_email
```
