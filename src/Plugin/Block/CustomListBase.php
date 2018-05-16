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
   * Get custom list insert configuration.
   *
   * TODO: Use Entity Browser for this or some other way to select!
   *
   * @return array
   *   Insert configuration.
   */
  protected function getInsertionConfig() {
    return [
      [
        // 1.
        'position' => 0,
        'type' => 'entity',
        'config' => [
          'type' => 'node',
          'view_mode' => 'default',
          'id' => '2',
        ],
      ],
      [
        // 3.
        'position' => 2,
        'type' => 'block',
        'config' => [
          'type' => 'search_form_block',
          'config' => [
            'id' => 'block_list_search_form_block',
            'label' => 'Test View based solution -> Block',
            'provider' => 'search',
            'label_display' => 'visible',
          ],
        ],
      ],
    ];
  }

  /**
   * Get default view configuration.
   *
   * @return array
   *   Returns view configuration.
   */
  abstract protected function getViewConfig();

}
