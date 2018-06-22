<?php

namespace Drupal\custom_list_search_api\Plugin\views\style;

use Drupal\views\Entity\View;
use Drupal\custom_list\Plugin\views\style\CustomListBase;

/**
 * Search API custom list view style.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "custom_list_search_api",
 *   title = @Translation("Search API custom list"),
 *   help = @Translation("Displays rows one after another with inserts of manually picked entities or blocks."),
 *   theme = "views_view_unformatted",
 *   display_types = {"normal"}
 * )
 */
class CustomListSearchApi extends CustomListBase {

  /**
   * {@inheritdoc}
   */
  protected function getFilter(View $storage, array $entities) {
    $table = $storage->get('base_table');
    $field = $storage->get('base_field');

    $not_in_values = [];
    $unique_id = '';
    foreach ($entities as $entity) {
      $entity_type = $entity->getEntityTypeId();
      $entity_id = $entity->id();

      // TODO: Make language independent filtering!!!
      $search_api_id = "entity:{$entity_type}/{$entity_id}:en";
      $unique_id .= "_{$entity_type}-{$entity_id}";

      $not_in_values[$search_api_id] = $search_api_id;
    }

    return [
      "id" => "custom_list_search_api_{$table}_{$field}{$unique_id}",
      "table" => $table,
      "field" => $field,
      "relationship" => "none",
      "group_type" => "group",
      "admin_label" => "",
      "operator" => "not in",
      "value" => $not_in_values,
      "group" => "1",
      "exposed" => FALSE,
      "is_grouped" => FALSE,
      "plugin_id" => "custom_list_search_api_id",
    ];
  }

}
