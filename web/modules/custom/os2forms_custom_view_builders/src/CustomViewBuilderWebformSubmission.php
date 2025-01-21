<?php

namespace Drupal\os2forms_custom_view_builders;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\webform\Plugin\WebformElementAttachmentInterface;
use Drupal\webform\Plugin\WebformElementCompositeInterface;
use Drupal\webform\Twig\WebformTwigExtension;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformYaml;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionViewBuilder;

/**
 * Defines a class override webform submission view builder.
 *
 * @internal
 * This file is pretty much a copy and paste of the default view builder. The
 * few alterations are marked with "@internal Os2Forms changes start/end"
 * The file is ignored by phpstan because it is a copy of externally contributed
 * code.
 *
 * @see \Drupal\webform\Entity\WebformSubmission
 */
class CustomViewBuilderWebformSubmission extends WebformSubmissionViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    if (empty($entities)) {
      return;
    }

    /** @var \Drupal\webform\WebformSubmissionInterface[] $entities */
    foreach ($entities as $id => $webform_submission) {
      $webform = $webform_submission->getWebform();

      if ($view_mode === 'preview') {
        $options = [
          'view_mode' => $view_mode,
          'excluded_elements' => $webform->getSetting('preview_excluded_elements'),
          'exclude_empty' => $webform->getSetting('preview_exclude_empty'),
          'exclude_empty_checkbox' => $webform->getSetting('preview_exclude_empty_checkbox'),
        ];
      }
      else {
        // Track PDF.
        // @see webform_entity_print.module
        $route_name = $this->routeMatch->getRouteName();
        $pdf = in_array($route_name, ['entity_print.view.debug', 'entity_print.view']) || \Drupal::request()->request->get('_webform_entity_print');
        $options = [
          'view_mode' => $view_mode,
          'excluded_elements' => $webform->getSetting('submission_excluded_elements'),
          'exclude_empty' => $webform->getSetting('submission_exclude_empty'),
          'exclude_empty_checkbox' => $webform->getSetting('submission_exclude_empty_checkbox'),
          'pdf' => $pdf,
        ];
      }

      switch ($view_mode) {
        case 'twig':
          // @see \Drupal\webform_entity_print_attachment\Element\WebformEntityPrintAttachment::getFileContent
          $build[$id]['data'] = WebformTwigExtension::buildTwigTemplate(
            $webform_submission,
            $webform_submission->webformViewModeTwig
          );
          break;

        case 'yaml':
          // Note that the YAML view ignores all access controls and excluded
          // settings.
          $data = $webform_submission->toArray(TRUE, TRUE);
          // Covert computed element value markup to strings to
          // 'Object support when dumping a YAML file has been disabled' errors.
          WebformElementHelper::convertRenderMarkupToStrings($data);
          $build[$id]['data'] = [
            '#theme' => 'webform_codemirror',
            '#code' => WebformYaml::encode($data),
            '#type' => 'yaml',
          ];
          break;

        case 'text':
          $elements = $webform->getElementsInitialized();
          $build[$id]['data'] = [
            '#theme' => 'webform_codemirror',
            '#code' => $this->buildElements($elements, $webform_submission, $options, 'text'),
          ];
          break;

        case 'table':
          /* @internal Os2Forms changes start */
          $elements = $webform->getElementsInitializedAndFlattened();
          /* @internal Os2Forms changes end */
          $build[$id]['data'] = $this->buildTable($elements, $webform_submission, $options);
          break;

        default:
        case 'html':
          $elements = $webform->getElementsInitialized();
          $build[$id]['data'] = $this->buildElements($elements, $webform_submission, $options);
          break;
      }
    }

    EntityViewBuilder::buildComponents($build, $entities, $displays, $view_mode);
  }

  /**
   * {@inheritdoc}
   */
  public function buildTable(array $elements, WebformSubmissionInterface $webform_submission, array $options = []) {
    $rows = [];
    foreach ($elements as $key => $element) {
      if (!$this->isElementVisible($element, $webform_submission, $options)) {
        continue;
      }

      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $this->elementManager->getElementInstance($element);

      // Replace tokens before building the element.
      $webform_element->replaceTokens($element, $webform_submission);

      // Check if empty value is excluded.
      if ($webform_element->isEmptyExcluded($element, $options) && !$webform_element->getValue($element, $webform_submission, $options)) {
        continue;
      }

      $title = $element['#admin_title'] ?: $element['#title'] ?: '(' . $key . ')';
      // Note: Not displaying an empty message since empty values just render
      // an empty table cell.
      /* @internal Os2Forms changes start */
      switch ($element['#type']) {
        case 'container':
          // Prevent row rendering.
          continue 2;

        case 'fieldset':
          $html = [
            '#plain_text' => '',
          ];
          break;

        case 'webform_markup':
          $html = $webform_element->buildHtml($element, $webform_submission, $options);
          $title = '';
          break;

        default:
          $html = $webform_element->formatHtml($element, $webform_submission, $options);
      }
      /* @internal Os2Forms changes end */
      $rows[$key] = [
        ['header' => TRUE, 'data' => $title],
        ['data' => (is_string($html)) ? ['#markup' => $html] : $html],
      ];
    }

    return [
      '#type' => 'table',
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['webform-submission-table'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function isElementVisible(array $element, WebformSubmissionInterface $webform_submission, array $options) {
    // Checked excluded elements.
    if (isset($element['#webform_key']) && isset($options['excluded_elements'][$element['#webform_key']])) {
      return FALSE;
    }

    // Checked excluded attachments, except from composite elements.
    // @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::formatComposite
    if (!empty($options['exclude_attachments'])) {
      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $this->elementManager->getElementInstance($element, $webform_submission);
      if ($webform_element instanceof WebformElementAttachmentInterface
        && !$webform_element instanceof WebformElementCompositeInterface) {
        return FALSE;
      }
    }

    // Check if the element is conditionally hidden.
    if (!$this->conditionsValidator->isElementVisible($element, $webform_submission)) {
      return FALSE;
    }

    // Check if ignore access is set.
    // This is used email handlers to include administrative elements in emails.
    if (!empty($options['ignore_access'])) {
      return TRUE;
    }

    // Check the element's #access.
    if (isset($element['#access']) && (($element['#access'] instanceof AccessResultInterface && $element['#access']->isForbidden()) || ($element['#access'] === FALSE))) {
      return FALSE;
    }

    /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
    $webform_element = $this->elementManager->getElementInstance($element, $webform_submission);

    /* @internal Os2Forms changes start */
    $elementDisplayMode = $webform_element->getElementProperty($element, 'display_on');

    if ($elementDisplayMode == 'form') {
      return FALSE;
    }
    /* @internal Os2Forms changes end */

    // Finally, check the element's 'view' access.
    return $webform_element->checkAccessRules('view', $element) ? TRUE : FALSE;
  }

}
