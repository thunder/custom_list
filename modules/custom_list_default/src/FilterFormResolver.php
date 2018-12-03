<?php

namespace Drupal\custom_list_default;

use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * The filter form resolver service.
 */
class FilterFormResolver implements FilterFormResolverInterface {

  /**
   * Mapping list of form information for filter handlers.
   *
   * @var array
   */
  protected static $filterMapping = [
    'string' => 'String',
    'language' => 'Language',
    'numeric' => 'Numeric',
    'boolean' => 'Boolean',
    'date' => 'Date',
    'user_name' => 'User',
    'taxonomy_index_tid_depth' => 'Taxonomy',
    'taxonomy_index_tid' => 'Taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormInfo(FilterPluginBase $filter) {
    $filter_id = $filter->getPluginId();

    if (isset(static::$filterMapping[$filter_id])) {
      return [
        'id' => static::$filterMapping[$filter_id],
      ];
    }

    return [];
  }

}
