<?php

namespace Drupal\custom_list_default\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Provides entity ID filter for custom list.
 *
 * TODO: Try to hide filter from View UI.
 *
 * @package Drupal\custom_list_default\Plugin\views\filter
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("custom_list_id")
 */
class CustomListId extends InOperator {

  /**
   * {@inheritdoc}
   */
  protected function opSimple() {
    if (empty($this->value)) {
      return;
    }

    $this->ensureMyTable();

    // Different table field should be used.
    $real_field = $this->options['_real_field'];

    // We use array_values() because the checkboxes keep keys and that can cause
    // array addition problems.
    $this->query->addWhere($this->options['group'], "$this->tableAlias.$real_field", array_values($this->value), $this->operator);
  }

}
