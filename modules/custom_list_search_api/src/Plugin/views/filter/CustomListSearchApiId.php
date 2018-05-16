<?php

namespace Drupal\custom_list_search_api\Plugin\views\filter;

use Drupal\search_api\Plugin\views\filter\SearchApiFilterTrait;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Provides Search API integration filter for custom list.
 *
 * @package Drupal\custom_list_search_api\Plugin\views\filter
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("custom_list_search_api_id")
 */
class CustomListSearchApiId extends InOperator {

  use SearchApiFilterTrait;

  /**
   * No result for default usage.
   *
   * @return array
   *   Returns empty array for population of list in frontend.
   */
  public function getValueOptions() {
    $this->valueOptions = [];

    return $this->valueOptions;
  }

}
