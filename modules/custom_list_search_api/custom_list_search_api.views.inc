<?php

/**
 * @file
 * Contains view integration hooks for custom list Search API.
 */

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
