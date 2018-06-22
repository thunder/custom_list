<?php

/**
 * @file
 * Contains view integration hooks for custom list Search API.
 */

use Drupal\views\ViewExecutable;

/**
 * Add Search API filter.
 *
 * Implements @see hook_views_data_alter().
 *
 * @param array $data
 *   An array of all information about Views tables and fields, collected from
 *   hook_views_data(), passed by reference.
 */
function custom_list_search_api_views_data_alter(array &$data) {

  foreach ($data as $table_name => $fields) {
    // This filter is required, because it will be used internally to filter
    // custom list entities from result.
    if (isset($fields['search_api_id'])) {
      $data[$table_name]['search_api_id']['filter']['id'] = 'custom_list_search_api_id';
    }
  }

}

/**
 * Implements @see hook_views_post_execute().
 */
function custom_list_search_api_views_post_execute(ViewExecutable $view) {
  if (empty($view->result)) {
    return;
  }

  // Handle uniques only relevant for this styles provided by this module.
  if ($view->getStyle()->getPluginId() !== 'custom_list_search_api') {
    return;
  }

  /** @var \Drupal\custom_list\Plugin\views\style\CustomListBase $style_plugin */
  $style_plugin = $view->getStyle();
  if (!$style_plugin->usesUniqueEntities()) {
    return;
  }

  $entities = [];
  foreach ($view->result as $row) {
    if (isset($row->_relationship_objects)) {
      foreach ($row->_relationship_objects as $relationship_objects) {
        /** @var \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $relationship_object */
        foreach ($relationship_objects as $relationship_object) {
          $entities[] = $relationship_object->getValue();
        }
      }
    }
  }

  /** @var \Drupal\custom_list\UniqueEntitiesStorageInterface $unique_storage */
  $unique_storage = \Drupal::service('custom_list.unique_entities_store');

  /** @var \Drupal\views\ViewEntityInterface $view_storage */
  $view_storage = $view->storage;

  $unique_storage->setIds($view_storage->get('base_table'), $view_storage->get('base_field'), $entities);
}
