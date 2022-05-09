# OS2Forms API handler

Send submission as JSON to an API endpoint.

This module uses a queue (see [Installation](#installation)) to send API
requests, i.e. the API requests are not sent immediately on form submission.

## Installation

Enable the module:

```sh
drush pm:enable os2forms_api_request_handler
```

Go to Administration > Configuration > System > Queues
(/admin/config/system/queues) and create an [Advanced
Queue](https://www.drupal.org/project/advancedqueue) with machine name
`os2forms_api_request_handler`.

Make sure that the queue is processed regularly, e.g. by [`drush
cron`](https://docs.drush.org/en/9.x/cron/) or by a cron job running the command

```sh
drush advancedqueue:queue:process os2forms_api_request_handler`
```

## Usage

Add an “API request handler” to a webform, fill in “API url” and “API token” and
save the handler.

For testing what’s actually sent to the API endpoint, use <https://webhook.site>
or similar tools to see the data.

## Request body

The JSON data sent to the API endpoint looks like

```json
{
  "data": {
    "webform": {
      "id": "…"
    },
    "submission": {
      "uuid": "…"
    }
  },
  "links": {
    // The sender url.
    "sender": "https://example.com",
    // API url for getting the actual submission data.
    "get_submission_url" => "https://example.com/webform_rest/…/submission/…"
  }
}
```

and the API token is sent in an authorization header:

```http
authorization: Token …
```
