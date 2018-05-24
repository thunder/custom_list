<?php

namespace Drupal\custom_list\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\views\Entity\View;

/**
 * Entity list base class.
 *
 * @package Drupal\custom_list\Plugin\Block
 */
abstract class CustomListBase extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $view_config = $this->getViewConfig();

    $view = new View($view_config, 'view');
    return $view->getExecutable()->render('custom_list_block');
  }

  /**
   * Provides form for adding inserts.
   *
   * @param array $selection
   *   Existing selection of entities.
   *
   * @return array
   *   Returns form elements.
   */
  protected function getInsertsForm(array $selection) {
    $inserts_form = [];

    $inserts_form['insert_selection'] = [
      '#type' => 'hidden',
      '#default_value' => json_encode($selection),
      '#attached' => [
        'library' => [
          'custom_list/insert_selector',
        ],
      ],
      '#attributes' => [
        'data-entity-browser' => 'entity_browser_selector',
      ],
    ];

    // TODO: configurable entity browsers - in some way!
    $inserts_form['entity_browser_selector'] = [
      '#type' => 'entity_browser',
      '#entity_browser' => 'try_for_custom_list',
    ];

    return $inserts_form;
  }

  /**
   * Extract values from insert selection form.
   *
   * @param array $insert_form_values
   *   Insert selection form values.
   *
   * @return array
   *   Returns insert selection list.
   */
  protected function fetchInsertSelection(array $insert_form_values) {
    return json_decode($insert_form_values['insert_selection'], TRUE);
  }

  /**
   * Get default view configuration.
   *
   * @return array
   *   Returns view configuration.
   */
  abstract protected function getViewConfig();

}
