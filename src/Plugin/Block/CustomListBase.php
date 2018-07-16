<?php

namespace Drupal\custom_list\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
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
      '#entity_browser' => 'custom_list_articles',
    ];

    $inserts_form['add_block'] = [
      '#type' => 'link',
      '#title' => $this->t('Add insertion block'),
      '#url' => Url::fromRoute('custom_list.add_block_list'),
      '#attributes' => [
        'class' => ['use-ajax', 'button', 'custom-list__add_block__button'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
      '#attached' => [
        'library' => [
          'custom_list/add_block',
        ],
      ],
    ];

    return $inserts_form;
  }

  /**
   * Get unique selector sub-form.
   *
   * @param bool $preselected_value
   *   Preselected value for unique selector.
   *
   * @return array
   *   Return sub-form for unique selector.
   */
  protected function getUniqueSelector($preselected_value) {
    $unique_selector_form = [];

    $unique_selector_form['unique_selection'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unique'),
      '#default_value' => $preselected_value,
    ];

    return $unique_selector_form;
  }

  /**
   * Fetch unique selector value.
   *
   * @param array $unique_form_values
   *   Form values for unique option.
   *
   * @return bool
   *   Return if unique options is selected.
   */
  protected function fetchUniqueSelector(array $unique_form_values) {
    return (bool) $unique_form_values['unique_selection'];
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
