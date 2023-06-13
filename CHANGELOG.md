<!-- markdownlint-disable MD024 -->
# Changelog for selvbetjening.aarhuskommune.dk

Nedenfor ses dato for release og beskrivelse af opgaver som er implementeret.

## [Under udvikling]

* Fix bug in login display where login link displayed on pages without login
  enabled.
* Installerede `Webform Validation` modulet.
* Minor type fixes to FBS handler
* Tilføjede patch for at tillade filtrering af formularer på kategori
  <https://www.drupal.org/files/issues/2022-10-07/3313766-8.patch>.
* Tilføjede mulighed for at søge på navne på brugerlisten.
* Opdaterede til [OS2Forms sync
  1.1.2](https://github.com/itk-dev/os2forms_sync/releases/tag/1.1.2).

## [2.2.1]

* Opdaterede til [OS2Forms CPR Lookup
  1.8.0](https://github.com/itk-dev/os2forms_cpr_lookup/releases/tag/1.8.0).
* Opdaterede til [OS2Forms CVR Lookup
  1.4.0](https://github.com/itk-dev/os2forms_cvr_lookup/releases/tag/1.4.0).

## [2.2.0]

* Opdaterede til [OS2Forms GetOrganized
  1.1.1](https://github.com/OS2Forms/os2forms_get_organized/releases/tag/1.1.1)
* Opdaterede `Content Translation` modulets konfiguration for at give
  `Selvbetjeningsdesigner` rollen adgang til oversættelse.
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/184>)
* Opdaterede CPR-opslagskonfiguration
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/182>)
* Opdaterede til [OS2Forms Organisation
  1.1.1](https://github.com/itk-dev/os2forms_organisation/releases/tag/1.1.1)
  med søgning.
* Opdaterede til [OS2Forms Digital post
  1.2.3](https://github.com/itk-dev/os2forms_digital_post/releases/tag/1.2.3).

## [2.1.0]

* os2forms/os2forms_get_organized (1.1.0)
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/177>).
* Opdaterede `itk-dev/os2forms_nemlogin_openid_connect`, `os2forms/os2forms` og
  `os2forms/os2forms_get_organized`
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/176>).
* Installerede `os2forms/os2forms_get_organized`
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/174>).
* Opdaterede CVR-elementer og konfiguration af CVR-opslagservice
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/172>).
* Opdaterede CPR- og CVR-opslagservicer og konfiguration af samme
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/168>).
* Installerede FBS-modulet
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/171>).
* Fiksede tjek for webformulargrupper
  (<https://github.com/itk-dev/os2forms_selvbetjening/pull/166>).
* Updaterede os2web/os2web_datalookup
  (<https://github.com/OS2web/os2web_datalookup/compare/1.5.1...1.5.2>).
* Fiksede Xdebug-opsætning.
* Fiksede GitHub Action til at installere site.
* Fjernede “custom” `os2forms_user_field_lookup`-modul.
* Opdaterede “config ignore”-regler
* Tilføjet handler til oprettelse af bruger i FBS med guardian.
* os2forms_failed_jobs (1.1.0)
* itk-dev/itkdev-booking (1.0.1)
* Tilføjet language switcher block
* Updated composite element styling
* Added translation block in top

## [2.0.0] 29.03.2023

* Gjorde det muligt at installere sitet forfra og opdaterede
  installationsvejledning.

### Opdateret

* Hide scrollbar on sidebar navigation
* Opdaterede os2forms/os2forms_rest_api
* os2forms_digital_post (1.2.0)
* Remove dompdf
* Opdaterede docker compose-setup.
* Disable IP tracking
* Deny anonymous access to webform node revisions
* Allow access to config translations for user role forloeb_designer
* Remove revisions tab
* Håndtering af os2web/os2web_nemlogin konfiguration.
* Disable caching on failed jobs list.
* itk-dev/getorganized-api-client-php (1.2.0)
* Udvidet GetOrganized handler med funktionalitet
  til at arkivere vedhæftede filer som bilag i GO.
* Konfiguration ændringer
  * Aktiverede `Signature` og `Email confirm` webform elementer.
  * Brug af `daemon` til GetOrganized og REST API queues.
  * Sortede maestro flows efter `id` på `/maestro-all-flows`.
* Tilføjede mulighed for konfigration af webform category
* Opdaterede os2forms/os2forms_forloeb og drupal/ultimate_cron
* Fjernede `user_default_page`
* Tilføjede `os2forms_attachment`

## [1.7.1] 24.02.2023

* Behandl digital post-køer via rigtig [`cron`](https://en.wikipedia.org/wiki/Cron).

## [1.7.0] 13.02.2023

### Tilføjet

* Installerede [OS2Forms organisation
  1.0.0](https://github.com/itk-dev/os2forms_organisation/releases/tag/1.0.0).
* Phpstan config

### Opdateret

* os2forms_failed_jobs (1.0.1)
* os2forms_webform_submission_log (1.0.0)
* os2forms_digital_post (1.1.2)
* Danske oversættelser

## 06.02.2023

### Tilføjet

* Understøttelse af Næste generation Digital Post (NgDP)
  ([SF1601](https://digitaliseringskataloget.dk/integration/sf1601)) i
  webformularhandler.
* GitHub Action-tjek for at `CHANGELOG.md` er opdateret.
* Tilføjet at sider (node) med adgang anonym kan besøges af alle anonyme brugere
  og drupal-brugere selvom de ikke har rettigheder til siden (node).
* Tilføjet Book aarhus.
* Tilføj conditions til "Mere"-element
* Tilføjet Api request handler logging.
* Tilføjet get organized handler logging.
* Add handler id to errors.
* Opdaterede til [OS2Forms CPR Lookup
  1.7.0](https://github.com/itk-dev/os2forms_cpr_lookup/releases/tag/1.7.0).
* Opdaterede til [OS2Forms CVR Lookup
  1.3.0](https://github.com/itk-dev/os2forms_cvr_lookup/releases/tag/1.3.0).
* Installerede [OS2Forms sync
  1.0.0](https://github.com/itk-dev/os2forms_sync/releases/tag/1.0.0).
* Opgraderede til [PHP 8.1](https://www.php.net/releases/8.1/en.php).
* Installerede [Os2forms Failed jobs
  1.0.0](https://github.com/itk-dev/os2forms_failed_jobs/releases/tag/1.0.0).
* Tilføjede danske oversættelser.
* Tilføjede patch
  <https://www.drupal.org/files/issues/2022-03-15/content_entity_revisions-3260602-07.patch>.

### Fix

* Begrænse workflow dropdown til bruger <https://github.com/itk-dev/os2forms_selvbetjening/pull/113>
* Unpublished indhold kan publiceres igen. <https://github.com/itk-dev/os2forms_selvbetjening/pull/112>
* Changed spacing in header and footer. <https://github.com/itk-dev/os2forms_selvbetjening/pull/121>

### Fjernet

* Fjerne handlers som ikke virker

## 14.12.2022

### Tilføjet

* Patch så sektionsoverskrifter kan fjernes fra submissions

### Fix

* CPR børn bugfix for sektioner

## 8.12.2022

### Tilføjet

* Logs på submissions
* Valg-liste: Likert: mindre til mere

## 29.11.2022

### Tilføjet

* Valg med magistratsafdelinger
* {Empty} oversat til {Tom}
* Nemlogin-logud knap flyttes til user menu modul
* Nemlogin-navn visning og håndtering hvis navn ikke er tilgængelig

### Fjernet

* Nemlogin-logud meddelelse deaktiveret

## 27.10.2022

### Tilføjet

* Genvejsknapper aktiveret
* Synlig linje om testmiljø eller lignende introduceret
* Selvbetjeningsdesigner fået adgang til redigering af formular-kilde (yml)

## 14.10.2022

### Tilføjet

* Computed twig aktiveret
* Book Aarhus modul

## 16.09.2022

### Tilføjet

* GO borgersager

[Under udvikling]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.2.1...HEAD
[2.2.1]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.2.0...2.2.1
[2.2.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.1.0...2.2.0
[2.1.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/2.0.0...2.1.0
[2.0.0]: https://github.com/itk-dev/os2forms_selvbetjening/compare/1.7.1...2.0.0
[1.7.1]: https://github.com/itk-dev/os2forms_selvbetjening/compare/1.7.0...1.7.1
[1.7.0]: https://github.com/itk-dev/os2forms_selvbetjening/releases/tag/1.7.0
