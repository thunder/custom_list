<?php

namespace Drupal\custom_list_default;

use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Interface for filter form resolver service.
 */
interface FilterFormResolverInterface {

  /**
   * Get a form information for filter handler.
   *
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
   *   The filter handler.
   *
   * @return array
   *   Returns form information for provided filter handler.
   */
  public function getFormInfo(FilterPluginBase $filter);

}
