<?php

/**
 * @file
 * Contains view integration hooks for custom list default.
 */

use Drupal\views\ViewExecutable;

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

/**
 * Implements @see hook_views_post_execute().
 */
function custom_list_default_views_post_execute(ViewExecutable $view) {
  if (empty($view->result)) {
    return;
  }

  // Handle uniques only relevant for this styles provided by this module.
  if ($view->getStyle()->getPluginId() !== 'custom_list_default') {
    return;
  }

  /** @var \Drupal\custom_list\Plugin\views\style\CustomListBase $style_plugin */
  $style_plugin = $view->getStyle();
  if (!$style_plugin->usesUniqueEntities()) {
    return;
  }

  $entities = [];
  foreach ($view->result as $row) {
    if (!empty($row->_entity)) {
      $entities[] = $row->_entity;
    }
  }

  /** @var \Drupal\custom_list\UniqueEntitiesStorageInterface $unique_storage */
  $unique_storage = \Drupal::service('custom_list.unique_entities_store');

  /** @var \Drupal\views\ViewEntityInterface $view_storage */
  $view_storage = $view->storage;

  $unique_storage->setIds($view_storage->get('base_table'), $view_storage->get('base_field'), $entities);
}
