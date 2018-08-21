<?php

/**
 * @file
 * Contains view integration hooks for custom list default.
 */

/**
 * Add base content ID filter.
 *
 * Implements @see hook_views_data_alter().
 *
 * @param array $data
 *   An array of all information about Views tables and fields, collected from
 *   hook_views_data(), passed by reference.
 */
function custom_list_default_views_data_alter(array &$data) {
  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
  $entity_type_manager = \Drupal::entityTypeManager();

  $supported_entity_types = ['node', 'media'];
  foreach ($supported_entity_types as $entity_type_id) {
    // Ensure that filter is enabled only for available definitions.
    try {
      $entity_type_definition = $entity_type_manager->getDefinition($entity_type_id);

      if (!$entity_type_definition) {
        continue;
      }
    }
    catch (Exception $e) {
      continue;
    }

    $data_table = $entity_type_definition->getDataTable();
    $entity_keys = $entity_type_definition->getKeys();
    if (isset($entity_keys['id'])) {
      $id_field = $entity_keys['id'];

      if (isset($data[$data_table][$id_field])) {
        $data[$data_table]['custom_list_default'] = [
          'filter' => ['id' => 'custom_list_id'],
        ];
      }
    }
  }

}
