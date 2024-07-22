<?php

/**
 * @file
 * The Views integration file.
 */

/**
 * Implements hook_views_data_alter().
 */
function extrafield_views_integration_views_data_alter(array &$data) {
  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $fieldManager */
  $fieldManager = Drupal::getContainer()->get('entity_field.manager');
  /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo */
  $bundleInfo = Drupal::getContainer()->get('entity_type.bundle.info');
  /** @var \Drupal\Core\Entity\EntityTypeInterface[] $type */
  $entityTypes = Drupal::entityTypeManager()->getDefinitions();

  foreach ($entityTypes as $entityType) {
    if ($entityType->getGroup() != 'content') {
      continue;
    }

    foreach ($bundleInfo->getBundleInfo($entityType->id()) as $bundleType => $bundle) {
      $extraFields = $fieldManager->getExtraFields($entityType->id(), $bundleType);
      if (!empty($extraFields['display'])) {
        foreach ($extraFields['display'] as $extraFieldName => $extraField) {
          if (!isset($extraField['render_class'])) {
            continue;
          }
          $viewsFieldName = 'extrafield_views_integration__' . $extraFieldName;

          $data[$entityType->id()][$viewsFieldName] = [
            'title' => t('Extrafield @label', ['@label' => $extraField['label']]),
            'help' => t('Integrate Extrafield: @label for entity type: @entityType', [
              '@label' => $extraField['label'],
              '@entity_type' => $entityType->getLabel(),
            ]),
            'field' => [
              'title' => t('Extrafield @label', ['@label' => $extraField['label']]),
              'help' => $extraField['description'],
              'id' => 'extrafield_views_integration',
              'render_class' => $extraField['render_class'],
            ],
          ];
        }
      }
    }
  }
}
