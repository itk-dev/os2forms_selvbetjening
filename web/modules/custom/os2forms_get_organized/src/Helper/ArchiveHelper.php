<?php

namespace Drupal\os2forms_get_organized\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\os2forms_get_organized\Exception\CitizenArchivingException;
use Drupal\os2forms_get_organized\Exception\GetOrganizedCaseIdException;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform_entity_print_attachment\Element\WebformEntityPrintAttachment;
use ItkDev\GetOrganized\Client;
use ItkDev\GetOrganized\Service\Cases;
use ItkDev\GetOrganized\Service\Documents;

/**
 * Helper for archiving documents in GetOrganized.
 */
class ArchiveHelper {

  const CITIZEN_CASE_TYPE_PREFIX = 'BOR';

  /**
   * The GetOrganized Client.
   *
   * @var \ItkDev\GetOrganized\Client|null
   */
  private ?Client $client = NULL;

  /**
   * The GetOrganized Documents Service.
   *
   * @var \ItkDev\GetOrganized\Service\Documents|null
   */
  private ?Documents $documentService = NULL;

  /**
   * The GetOrganized Cases Service.
   *
   * @var \ItkDev\GetOrganized\Service\Cases|null
   */
  private ?Cases $caseService = NULL;

  /**
   * The ConfigFactoryInterface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $config;

  /**
   * The EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs an ArchiveHelper object.
   */
  public function __construct(ConfigFactoryInterface $config, EntityTypeManagerInterface $entityTypeManager) {
    $this->config = $config;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Adds document to GetOrganized case.
   */
  public function archive(string $submissionId, array $handlerConfiguration) {
    // Setup Client and services.
    if (NULL === $this->client) {
      $this->setupClient();
    }

    if (NULL === $this->caseService) {
      /** @var \ItkDev\GetOrganized\Service\Cases $caseService */
      $caseService = $this->client->api('cases');
      $this->caseService = $caseService;
    }

    if (NULL === $this->documentService) {
      /** @var \ItkDev\GetOrganized\Service\Documents $docService */
      $docService = $this->client->api('documents');
      $this->documentService = $docService;
    }

    // Detect which archiving method is required.
    $archivingMethod = $handlerConfiguration['choose_archiving_method']['archiving_method'];

    if ('archive_to_case_id' === $archivingMethod) {
      $this->archiveToCaseId($submissionId, $handlerConfiguration);
    }
    elseif ('archive_to_citizen' === $archivingMethod) {
      $this->archiveToCitizen($submissionId, $handlerConfiguration);
    }

  }

  /**
   * Sets up Client.
   */
  private function setupClient() {
    $config = $this->config->get('os2forms_get_organized');
    $username = $config->get('username');
    $password = $config->get('password');
    $baseUrl = $config->get('base_url');

    $this->client = new Client($username, $password, $baseUrl);
  }

  /**
   * Gets WebformSubmission from id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getSubmission(string $submissionId) {
    $storage = $this->entityTypeManager->getStorage('webform_submission');
    return $storage->load($submissionId);
  }

  /**
   * Archives document to GetOrganized case id.
   */
  private function archiveToCaseId(string $submissionId, array $handlerConfiguration) {

    /** @var \Drupal\webform\Entity\WebformSubmission $submission */
    $submission = $this->getSubmission($submissionId);

    $getOrganizedCaseId = $handlerConfiguration['choose_archiving_method']['case_id'];
    $webformAttachmentElementId = $handlerConfiguration['general']['attachment_element'];
    $shouldBeFinalized = $handlerConfiguration['general']['should_be_finalized'] ?? FALSE;

    // Ensure case id exists.
    $case = $this->caseService->getByCaseId($getOrganizedCaseId);

    if (!$case) {
      $message = sprintf('Could not find a case with id %s.', $getOrganizedCaseId);
      throw new GetOrganizedCaseIdException($message);
    }

    $this->uploadDocumentToCase($getOrganizedCaseId, $webformAttachmentElementId, $submission, $shouldBeFinalized);
  }

  /**
   * Archives document to GetOrganized citizen subcase.
   */
  private function archiveToCitizen(string $submissionId, array $handlerConfiguration) {
    // Step 1: Find/create parent case
    // Step 2: Find/create subcase
    // Step 3: Upload to subcase.
    if (NULL === $this->client) {
      $this->setupClient();
    }

    /** @var \Drupal\webform\Entity\WebformSubmission $submission */
    $submission = $this->getSubmission($submissionId);

    $cprValueElementId = $handlerConfiguration['choose_archiving_method']['cpr_value_element'];
    $cprElementValue = $submission->getData()[$cprValueElementId];

    $cprNameElementId = $handlerConfiguration['choose_archiving_method']['cpr_name_element'];
    $cprNameElementValue = $submission->getData()[$cprNameElementId];

    // Step 1: Find/create parent case.
    $caseQuery = [
      'FieldProperties' => [
           [
             'InternalName' => 'ows_CCMContactData_CPR',
             'Value' => $cprElementValue,
           ],
      ],
      'CaseTypePrefixes' => [
        self::CITIZEN_CASE_TYPE_PREFIX,
      ],
      'LogicalOperator' => 'AND',
      'ExcludeDeletedCases' => TRUE,
      'ReturnCasesNumber' => 25,
    ];

    $caseResult = $this->caseService->FindByCaseProperties(
      $caseQuery
    );

    // Subcases may also contain contain the 'ows_CCMContactData_CPR' property,
    // i.e. we need to check result cases are not subcases.
    // $caseResult will always contain the 'CasesInfo' key,
    // and its value will always be an array.
    $caseInfo = array_filter($caseResult['CasesInfo'], function ($caseInfo) {
      // Parent cases are always on the form AAA-XXXX-XXXXXX,
      // Subcases are always on the form AAA-XXXX-XXXXXX-XXX,
      // I.e. we can filter out subcases by checking number of dashes in id.
      return 2 === substr_count($caseInfo['CaseID'], '-');
    });

    $parentCaseCount = count($caseInfo);

    if (0 === $parentCaseCount) {
      $parentCaseId = $this->createCitizenCase($cprElementValue, $cprNameElementValue);
    }
    elseif (1 < $parentCaseCount) {
      $message = sprintf('Too many (%d) parent cases.', $parentCaseCount);
      throw new CitizenArchivingException($message);
    }
    else {
      $parentCaseId = $caseResult['CasesInfo'][0]['CaseID'];
    }

    // Step 2: Find/create subcase.
    $subcaseName = $handlerConfiguration['choose_archiving_method']['sub_case_title'];

    $subCasesQuery = [
      'FieldProperties' => [
        [
          'InternalName' => 'ows_CaseId',
          'Value' => $parentCaseId . '-',
          'ComparisonType' => 'Contains',
        ],
        [
          'InternalName' => 'ows_Title',
          'Value' => $subcaseName,
          'ComparisonType' => 'Equal',
        ],
      ],
      'CaseTypePrefixes' => [
        self::CITIZEN_CASE_TYPE_PREFIX,
      ],
      'LogicalOperator' => 'AND',
      'ExcludeDeletedCases' => TRUE,
      // Unsure how many subcases may exist, but fetching 25 should be enough.
      'ReturnCasesNumber' => 25,
    ];

    $subCases = $this->caseService->FindByCaseProperties(
      $subCasesQuery
    );

    $subCaseCount = count($subCases['CasesInfo']);

    if (0 === $subCaseCount) {
      $subCaseId = $this->createSubCase($parentCaseId, $subcaseName);
    }
    elseif (1 === $subCaseCount) {
      $subCaseId = $subCases['CasesInfo'][0]['CaseID'];
    }
    else {
      $message = sprintf('Too many (%d) subcases with the name %s', $subCaseCount, $subcaseName);
      throw new CitizenArchivingException($message);
    }

    // Step 3: Upload to subcase.
    $webformAttachmentElementId = $handlerConfiguration['general']['attachment_element'];
    $shouldBeFinalized = $handlerConfiguration['general']['should_be_finalized'] ?? FALSE;

    $this->uploadDocumentToCase($subCaseId, $webformAttachmentElementId, $submission, $shouldBeFinalized);
  }

  /**
   * Creates citizen parent case in GetOrganized.
   */
  private function createCitizenCase($cprElementValue, $cprNameElementValue) {

    $metadataArray = [
      'ows_Title' => $cprElementValue . ' - ' . $cprNameElementValue,
      'ows_CCMContactData' => $cprNameElementValue . ';#;#' . $cprElementValue . ';#;#',
      'ows_CCMContactData_CPR' => $cprElementValue,
      'ows_CaseStatus' => 'Åben',
    ];

    $response = $this->caseService->createCase(self::CITIZEN_CASE_TYPE_PREFIX, $metadataArray);

    // Example response.
    // {"CaseID":"BOR-2022-000046","CaseRelativeUrl":"\/cases\/BOR12\/BOR-2022-000046",...}.
    return $response['CaseID'];
  }

  /**
   * Creates citizen subcase in GetOrganized.
   */
  private function createSubCase($caseId, string $caseName) {

    $metadataArray = [
      'ows_Title' => $caseName,
      'ows_CCMParentCase' => $caseId,
      'ows_ContentTypeId' => '0x0100512AABDB08FA4fadB4A10948B5A56C7C01',
      'ows_CaseStatus' => 'Åben',
    ];

    $response = $this->caseService->createCase(self::CITIZEN_CASE_TYPE_PREFIX, $metadataArray);

    // Example response.
    // {"CaseID":"BOR-2022-000046-001","CaseRelativeUrl":"\/cases\/BOR12\/BOR-2022-000046",...}.
    return $response['CaseID'];
  }

  /**
   * Uploads attachment document to GetOrganized case.
   */
  private function uploadDocumentToCase($caseId, $webformAttachmentElementId, WebformSubmission $submission, $shouldBeFinalized) {

    $element = $submission->getWebform()->getElement($webformAttachmentElementId, $submission);
    $fileContent = WebformEntityPrintAttachment::getFileContent($element, $submission);

    // Create temp file with attachment-element contents.
    $webformLabel = $submission->getWebform()->label();
    $tempFile = tempnam('/tmp', $webformLabel);
    file_put_contents($tempFile, $fileContent);

    $getOrganizedFileName = $webformLabel . '-' . $submission->serial() . '.pdf';

    $result = $this->documentService->AddToDocumentLibrary($tempFile, $caseId, $getOrganizedFileName);

    // Remove temp file.
    unlink($tempFile);

    // Handle finalization ("journalisering").
    if ($shouldBeFinalized) {
      if (isset($result['DocId'])) {
        $this->documentService->Finalize($result['DocId']);
      }
    }
  }

}
