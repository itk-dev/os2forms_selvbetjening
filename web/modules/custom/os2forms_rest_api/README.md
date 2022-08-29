# OS2Forms REST API

We use [Webform REST](https://www.drupal.org/project/webform_rest) to expose a
number of API endpoints.

## Authentication

We use [Key auth](https://www.drupal.org/project/key_auth) for authenticating
api users.

A user can access the REST API if

1. it has the “API user” (`api_user`) role and
2. has a generated key (User > Edit > Key authentication; `/user/«user
   id»/key-auth`).

The “API user” role gives read-only access to the API. To get read access, a
user must also have the “API user (write)” (`api_user_write`) role.

## Endpoints

| Name               | Path                                           | Methods |
|--------------------|------------------------------------------------|---------|
| Webform Elements   | `/webform_rest/{webform_id}/elements`          | GET     |
| Webform Fields     | `/webform_rest/{webform_id}/fields`            | GET     |
| Webform Submission | `/webform_rest/{webform_id}/submission/{uuid}` | GET     |
| Webform Submit     | `/webform_rest/submit`                         | POST    |

## Examples

### Submit webform

Request:

```sh
> curl --silent --location --header 'api-key: …' --header 'content-type: application/json' https://127.0.0.1:8000/webform_rest/submit --data @- <<'JSON'
{
  "webform_id": "{webform_id}",
  "//": "Webform field values (cf. /webform_rest/{webform_id}/fields)",
  "navn_": "Mikkel",
  "adresse": "Livets landevej",
  "mail_": "mikkel@example.com",
  "telefonnummer_": "12345678"
}
JSON
```

Response:

```json
{"sid":"6d95afe9-18d1-4a7d-a1bf-fd38c58c7733"}
```

(the `sid`value is a webform submission uuid).

### Modifying data response from Webform Submission GET

To allow updating data received from calling Webform Submission GET,
we have [patched](patches/webform_rest_submission.patch) `drupal/webform_rest`.

The patch adds the `WebformSubmissionDataEvent` event and ensures
dispatching of such an event during a Webform Submission GET request.

This allows us to modify the response data by simply adding a
[WebformSubmissionEventSubscriber](src/EventSubscriber/WebformSubmissionEventSubscriber.php).

#### Example file modifications

Default response:
```json
{
  …,
  "data": {
    "navn": "Jeppe",
    "telefon": "87654321"
    "dokumenter": {
      "12",
      "13"
    }
  }
}
```

Modified response with linked entities:
```json
{
  …,
  "data": {
    "navn": "Jeppe",
    "telefon": "87654321"
    "dokumenter": [
      "12",
      "13"
    ],
    "linked": {
      "dokumenter": {
        "12": {
          "id": "12",
          "url": "https://127.0.0.1:8000/system/files/webform/some_webform/67/attachment1.pdf",
          "mime_type": "application/pdf",
          "size": "14072"
        },
        "13": {
          "id": "13",
          "url": "https://127.0.0.1:8000/system/files/webform/some_webform/68/attachment2.pdf",
          "mime_type": "application/pdf",
          "size": "14072"
        }
      }
    }
  }
}
```

Such that, you can get the actual file by just calling the linked document url.

```sh
> curl --silent --header 'api-key: …' https://127.0.0.1:8000/system/files/webform/some_webform/67/attachment1.pdf
```

## Custom access control

To limit access to webforms, you can specify a list of API users that are
allowed to access a webform's data via the API.

Go to Settings > General > Third party settings > OS2Forms > REST API to specify
which users can access a webform's data. **If no users are specified, all API
users can access the data.**

### Technical details

The custom access check is implemented in an event subscriber listening on the
`KernelEvents::REQUEST` event. See
[EventSubscriber::onRequest](src/EventSubscriber/EventSubscriber.php) for
details.

In order to make documents accessible for api users the Key auth `authentication_provider`
service has been overwritten to be global. See [os2forms_rest_api.services](os2forms_rest_api.services.yml).
