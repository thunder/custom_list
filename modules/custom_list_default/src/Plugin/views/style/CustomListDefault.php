<?php

namespace Drupal\custom_list_default\Plugin\views\style;

use Drupal\views\Entity\View;
use Drupal\custom_list\Plugin\views\style\CustomListBase;

/**
 * Default content entity ID filter for custom list.
 *
 * TODO: Try to hide this style from View UI.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "custom_list_default",
 *   title = @Translation("Default custom list"),
 *   help = @Translation("Displays rows one after another with inserts of manually picked entities or blocks."),
 *   theme = "views_view_unformatted",
 *   display_types = {"normal"}
 * )
 */
class CustomListDefault extends CustomListBase {

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

      $unique_id .= "_{$entity_type}-{$entity_id}";

      $not_in_values[$entity_id] = $entity_id;
    }

    return [
      'id' => "custom_list_default_{$table}_{$field}_{$unique_id}",
      'table' => $table,
      'field' => 'custom_list_default',
      '_real_field' => $field,
      'relationship' => 'none',
      'group_type' => 'group',
      'admin_label' => '',
      'operator' => 'not in',
      'value' => $not_in_values,
      'group' => '1',
      'exposed' => FALSE,
      'is_grouped' => FALSE,
      'plugin_id' => 'custom_list_id',
    ];
  }

}
