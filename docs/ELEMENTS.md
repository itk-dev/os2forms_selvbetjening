# Elements

This contains a list of enabled elements as of May 2025,
combined with their number of usages, source, module and a description.

As of May 2025 there were 739 webforms.

Due to the amount of elements we attempt grouping them by their functionality.

The danish names are listed followed by the machine name in parentheses.

## Element configuration and usage

Elements can be enabled/disabled on `admin/structure/webform/config/elements`.

For detecting usage of elements in webforms go to `/admin/os2forms_selvbetjening_deprecations/elements`.

## Base elements

### Checkbox (checkbox)

**Usage**: 283

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Single checkbox form element.

### Skjult (hidden)

**Usage**: 22

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Hidden element that can store information without it being
seen in the webform. This can be used to add data to all emails or attachments
generated.

### Tekstfelt (textfield)

**Usage**: 614

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Single-line text element.

### Tekstområde (textarea)

**Usage**: 456

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Multi-line text element.

## Advanced elements

### Antal (number)

**Usage**: 139

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Numeric element. Can be configured with a minimum and maximum.

### Autofuldfør (webform_autocomplete)

**Usage**: 5

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Provides a text field element with auto-completion.

### Bedømmelse (webform_rating)

**Usage**: 11

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Element for rating something.

### Booking (booking_element)

**Usage**: 6

**Source**: [OS2Forms Selvbetjening](https://github.com/itk-dev/os2forms_selvbetjening)

**Module**: `itkdev_booking`

**Description**: Provides a webform element for creating bookings through the
AAK booking service. See the [itkdev_booking](https://github.com/itk-dev/os2forms_selvbetjening/tree/develop/web/modules/custom/itkdev_booking)
module.

### E-mail (email)

**Usage**: 159

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Element for providing an e-mail. Validates that input is e-mail.

### Email confirm (webform_email_confirm)

**Usage**: 60

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Provides two input fields that must be valid e-mails and match.

### Remote select element (webform_remote_select_element)

**Usage**: 25

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Select element with values from a remote endpoint.

### Signatur (webform_signature)

**Usage**: 36

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Element that allows adding your signature.

### Skalér (webform_scale)

**Usage**: 6

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Allows rating something via a scale.

### Spænd (range)

**Usage**: 4

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Allows inputting a number in a specific range via a slider.

### Tekstformat (text_format)

**Usage**: 18

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Gives a multi-line textarea with formatting options given by
the configured format.

### Telefon (tel)

**Usage**: 170

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Phone number providing element.
Allows configuring to allow phone numbers according to specific countries.

### Terms of service (webform_terms_of_service)

**Usage**: 15

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Terms of service element.
Can show terms of service as popup modal or a slider.

### URL (url)

**Usage**: 9

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Validates that input in a valid URL.
Can be configured to a required length or pattern.

### Variant (webform_variant)

**Usage**: 0

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Allows variations of forms based on context.
See [Webform module now supports variants](https://www.drupal.org/node/3104280).

### Værdi (value)

**Usage**: 7

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Element for storage of internal information.
Not shown on the webform but is part of submission data.

## Composite elements

### Adresse (webform_address)

**Usage**: 28

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Form element to collect address information
(street, city, state, zip).

### Custom composite (webform_custom_composite)

**Usage**: 130

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Form element to create custom composites using a grid/table
layout. Is used to combine other elements, i.e. a number and date element to
input distance covered on specific days.

### Kontakt (webform_contact)

**Usage**: 24

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Form element to collect contact information
(name, address, phone, email).

## Options elements

### Afkrydsningsfelter (checkboxes)

**Usage**: 107

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Multiple checkboxes. Allows re-use of [shared webform choices](https://selvbetjening.aarhuskommune.dk/da/admin/structure/webform/options/manage).

### Afkrydsningsfelter andet (webform_checkboxes_other)

**Usage**: 28

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: The above but with the ability to provide a custom value.

### Likert (webform_likert)

**Usage**: 33

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Form element where users can respond to multiple questions
using a [Likert](https://en.wikipedia.org/wiki/Likert_scale) scale.

### Radioknapper (radios)

**Usage**: 401

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Form element for a set of radio buttons. Allows re-use of
[shared webform choices](https://selvbetjening.aarhuskommune.dk/da/admin/structure/webform/options/manage).

### Radioknapper andet (webform_radios_other)

**Usage**: 39

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: The above but with the ability to provide a custom value.

### Table select (tableselect)

**Usage**: 6

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Vælg (select) but as a table.

### Table sort (webform_table_sort)

**Usage**: 1

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Allows sorting options in some sort of order. Allows re-use
of [shared webform choices](https://selvbetjening.aarhuskommune.dk/da/admin/structure/webform/options/manage).

### Vælg (select)

**Usage**: 196

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Form element for a drop-down menu or scrolling selection box.
Allows re-use of [shared webform choices](https://selvbetjening.aarhuskommune.dk/da/admin/structure/webform/options/manage).

### Vælg andet (webform_select_other)

**Usage**: 25

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: The above but with the ability to provide a custom value.

## File attachment elements

### Attachment Word Document (webform_entity_print_attachment)

**Usage**: 3

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Generates a Word Document attachment.

## Containers

### Beholder (container)

**Usage**: 47

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Provides an element that wraps child elements in a container.

### Detaljer (details)

**Usage**: 39

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Provides an interactive element that a user can open and close.

### Feltgruppe (fieldset)

**Usage**: 393

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Provides an element for a group of form elements.

### Flexbox layout (webform_flexbox)

**Usage**: 218

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Provides a flex(ible) box container used to layout elements
in multiple columns.

### Sektion (webform_section)

**Usage**: 452

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Provides an element for a section/group of form elements.

### Tabel (table)

**Usage**: 0

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Provides an element to render a table.

## File upload elements

### Billedfil (webform_image_file)

**Usage**: 13

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Provides a form element for uploading and saving an image file.

### Dokument fil (webform_document_file)

**Usage**: 102

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Provides a form element for uploading and saving a document.

### Fil (managed_file)

**Usage**: 190

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Provides a form element for uploading and saving a file.

### Lydfil (webform_audio_file)

**Usage**: 1

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Provides a form element for uploading and saving an audio file.

### Videofil (webform_video_file)

**Usage**: 0

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Provides a form element for uploading and saving a video file.

## Computed elements

### Computed Twig (webform_computed_twig)

**Usage**: 129

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Provides an item to display computed webform submission
values using Twig. Can get very complex quickly. Consider avoiding the use
if not strictly needed. See [Viden om OS2](https://os2forms.os2.eu/search?search_api_fulltext=computed&sort_by=search_api_relevance&sort_order=DESC).

## OS2Forms

### CPR / Navn validering (os2forms_person_lookup)

**Usage**: 127

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Adds input fields for CPR number and name. Validates that they match.

### OS2Forms Attachment (os2forms_attachment)

**Usage**: 457

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_attachment`

**Description**: Generates an attachment with submission data. Can be PDF or HTML.

### OS2Forms Kort (webform_map_field)

**Usage**: 13

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_webform_maps`

**Description**: Provides a map where a user may input markers. See [osforms_webform_maps](https://github.com/OS2Forms/os2forms/tree/develop/modules/os2forms_webform_maps).

### OS2Forms Session dynamic value field (os2forms_session_dynamic_value)

**Usage**: 0

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides a value from current user authentication session. In
Aarhus context this is the claims/keys provided by our IdP. To see available
claims/keys go to `/admin/config/system/os2web-nemlogin/test`.

## Date/time elements

### Dato (date)

**Usage**: 211

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Form element for date selection.

### Dato/tid (datetime)

**Usage**: 10

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Form element for date and time selection.

### Dato liste (datelist)

**Usage**: 17

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Form element for date and time selection using select menus
and text fields.

### Tid (webform_time)

**Usage**: 14

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Form element for time selection.

## DAWA - Danish Addresses Web API

See [os2forms_dawa](https://github.com/OS2Forms/os2forms/blob/develop/modules/os2forms_dawa/README.md)
for implementation details.

See [dawadocs](https://dawadocs.dataforsyningen.dk) for more information about DAWA.

### DAWA Address (autocomplete) (os2forms_dawa_address)

**Usage**: 140

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_dawa`

**Description**: Autocomplete addresses from DAWA.

### DAWA Address-Matrikula (autocomplete) (os2forms_dawa_address_matrikula)

**Usage**: 8

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_dawa`

**Description**: Autocomplete address from DAWA and automatic matrikula
(`erjerlav`) identifier from input address.

### DAWA Block (autocomplete) (os2forms_dawa_block)

**Usage**: 2

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_dawa`

**Description**: This is equivalent to fetching `ejerlav` in danish.

### DAWA Matrikula (autocomplete) (os2forms_dawa_matrikula)

**Usage**: 9

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_dawa`

**Description**: Autocomplete matrikula's from DAWA.

This is equivalent to fetching `jordstykker` in danish.

## Markup elements

### Indholdselement (webform_node_element)

**Usage**: 1

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Renders a node.

### Mere (webform_more)

**Usage**: 96

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: A form slideout element. Used to disclose extra information
upon clicking.

### Simpel HTML (webform_markup)

**Usage**: 514

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Element for rendering basic HTML markup.

### Vandret streg (webform_horizontal_rule)

**Usage**: 253

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Horizontal line. Often used when switching to new context in
webform.

### Vis (view)

**Usage**: 0

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: A view embed element.

## Organization

### Mine organisation data (mine_organisations_data_element)

**Usage**: 220

**Source**: [OS2Forms Organisation](https://github.com/itk-dev/os2forms_organisation)

**Module**: `os2forms_organisation`

**Description**: Used to display Aarhus kommune organisation data. Requires `AD`
OS2Forms session. See [os2forms_organisation](https://github.com/itk-dev/os2forms_organisation?tab=readme-ov-file)
for more details.

## NemId/MitID

Shows data for session user. All NemID/MitID elements require `Nemlogin`
OS2Forms session. See [os2forms_nemid](https://github.com/OS2Forms/os2forms/tree/develop/modules/os2forms_nemid)
for more information.

### NemID CPR (os2forms_nemid_cpr)

**Usage**: 218

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides CPR number.

### NemID UUID (os2forms_nemid_uuid)

**Usage**: 1

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides user UUID from nemid.

### NemID PID (os2forms_nemid_pid)

**Usage**: 2

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides user PID from nemid.

### NemID Nemlogin link (os2forms_nemid_nemlogin_link)

**Usage**: 5

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides a login link to authenticate via nemid.

### NemID Name (os2forms_nemid_name)

**Usage**: 283

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides name.

### NemID Address (os2forms_nemid_address)

**Usage**: 152

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides address.

### NemID Coaddress (os2forms_nemid_coaddress)

**Usage**: 1

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides address coaddress.

### NemID Street (os2forms_nemid_street)

**Usage**: 8

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides address street.

### NemID House nr (os2forms_nemid_house_nr)

**Usage**: 8

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides address house number.

### NemID Apartment nr (os2forms_nemid_apartment_nr)

**Usage**: 5

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides address apartment nr.

### NemID Floor (os2forms_nemid_floor)

**Usage**: 6

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides address floor.

### NemID City (os2forms_nemid_city)

**Usage**: 18

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides address city.

### NemID Postal code (os2forms_nemid_postal_code)

**Usage**: 13

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides address postal code.

### NemID Kommunekode (os2forms_nemid_kommunekode)

**Usage**: 9

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides address kommunekode.

## NemId/MitId Company

All NemID/MitID elements require `Nemlogin` OS2Forms session.
See [os2forms_nemid](https://github.com/OS2Forms/os2forms/tree/develop/modules/os2forms_nemid)
for more information.

### NemID Company CVR (os2forms_nemid_company_cvr)

**Usage**: 32

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides company CVR number.

### NemID Company CVR fetch data (os2forms_nemid_company_cvr_fetch_data)

**Usage**: 15

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides a CVR data fetcher element. Can be used to fill out
CVR elements when knowing just CVR number.

### NemID Company Name (os2forms_nemid_company_name)

**Usage**: 28

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides company name.

### NemID Company P-Number (os2forms_nemid_company_p_number)

**Usage**: 0

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides company p-number.

### NemID Company RID (os2forms_nemid_company_rid)

**Usage**: 0

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides company RID.

### NemID Company Address (os2forms_nemid_company_address)

**Usage**: 10

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides company address.

### NemID Company Street (os2forms_nemid_company_street)

**Usage**: 1

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides company address street.

### NemID Company HouseNr (os2forms_nemid_company_house_nr)

**Usage**: 0

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides company address house number.

### NemID Company ApartmentNr (os2forms_nemid_company_apartment_nr)

**Usage**: 0

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides company apartment number.

### NemID Company Floor (os2forms_nemid_company_floor)

**Usage**: 0

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides company address floor.

### NemID Company City (os2forms_nemid_company_city)

**Usage**: 0

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides company city.

### NemID Company PostalCode (os2forms_nemid_company_postal_code)

**Usage**: 0

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides company address postal code.

### NemID Company Kommunekode (os2forms_nemid_company_kommunekode)

**Usage**: 0

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides company address kommunekode.

## NemId/MitId Children

All NemID/MitID elements require `Nemlogin` OS2Forms session.
See [os2forms_nemid](https://github.com/OS2Forms/os2forms/tree/develop/modules/os2forms_nemid)
for more information.

### MitID Child CPR (os2forms_mitid_child_cpr)

**Usage**: 29

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides child CPR number.

### MitID Children Radios (os2forms_nemid_children_radios)

**Usage**: 1

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Radio buttons for selecting child.

### MitID Children Select (os2forms_nemid_children_select)

**Usage**: 38

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Dropdown for selecting child.

### MitID Child Name (os2forms_mitid_child_name)

**Usage**: 33

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides child name.

### MitID Child Other Guardian (os2forms_mitid_child_other_guardian)

**Usage**: 9

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides child guardians.

### MitID Child Address (os2forms_mitid_child_address)

**Usage**: 8

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides child address.

### MitID Child Coaddress (os2forms_mitid_child_coaddress)

**Usage**: 1

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides child coaddress.

### MitID Child street (os2forms_mitid_child_street)

**Usage**: 3

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides child address street.

### MitID Child House Nr (os2forms_mitid_child_house_nr)

**Usage**: 3

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides child address house number.

### MitID Child Apartment Nr (os2forms_mitid_child_apartment_nr)

**Usage**: 3

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides child address apartment number.

### MitID Child Floor (os2forms_mitid_child_floor)

**Usage**: 3

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides child address floor.

### MitID Child City (os2forms_mitid_child_city)

**Usage**: 3

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides child address city.

### MitID Child Postal Code (os2forms_mitid_child_postal_code)

**Usage**: 3

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides child address postal code.

### MitID Child kommunekode (os2forms_mitid_child_kommunekode)

**Usage**: 1

**Source**: [OS2Forms](https://github.com/OS2Forms/os2forms)

**Module**: `os2forms_nemid`

**Description**: Provides child address kommunekode.

## Payment

Payment via OS2Forms. See [os2forms_payment](https://github.com/itk-dev/os2forms_payment).

### OS2forms payment element (os2forms_payment)

**Usage**: 3

**Source**: [OS2Forms Payment](https://github.com/itk-dev/os2forms_payment)

**Module**: `os2forms_payment`

**Description**: Element for payment via OS2Forms.

## Buttons

### Send knap(per) (webform_actions)

**Usage**: 212

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Send/update buttons.

## Entity reference elements

### Term checkboxes (webform_term_checkboxes)

**Usage**: 1

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Provides an element for selecting terms as checkboxes.

### Term select (webform_term_select)

**Usage**: 1

**Source**: [Webform](https://www.drupal.org/project/webform)

**Module**: `webform`

**Description**: Provides an element for selecting terms as a dropdown.

## User fields

Provides information about authenticated drupal user.
See [os2forms_user_field_lookup](https://github.com/itk-dev/os2forms_user_field_lookup).

### User Field Element (user_field_element)

**Usage**: 4

**Source**: [OS2Forms User Field Lookup](https://github.com/itk-dev/os2forms_user_field_lookup)

**Module**: `os2forms_user_field_lookup`

**Description**: Provides the configured user field data.

### User Field Element (checkbox) (user_field_element_checkbox)

**Usage**: 0

**Source**: [OS2Forms User Field Lookup](https://github.com/itk-dev/os2forms_user_field_lookup)

**Module**: `os2forms_user_field_lookup`

**Description**: Provides the configured user field checkbox data.
