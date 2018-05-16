<?php

namespace Drupal\custom_list_search_api\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_list\Plugin\Block\CustomListBase;
use Drupal\search_api\Entity\Index;

/**
 * Provides a block for Search API custom list.
 *
 * @Block(
 *   id = "custom_list_search_api",
 *   admin_label = @Translation("Search API custom list")
 * )
 */
class CustomListSearchApi extends CustomListBase {

  /**
   * List of supported entity types.
   *
   * @var array
   */
  protected $allowedIndexes = [];

  /**
   * List of view modes for all entity types in search result.
   *
   * @var array
   */
  protected $viewModes = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->init();
  }

  /**
   * Initialize custom list search API block object.
   */
  protected function init() {
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    /** @var \Drupal\search_api\Entity\Index $index */
    foreach (Index::loadMultiple() as $index) {
      $index_id = $index->id();

      $this->allowedIndexes[$index_id] = $index->label();
      $this->viewModes[$index_id] = [
        'default' => $this->t('Default'),
      ];

      $data_source_ids = $index->getDatasourceIds();
      foreach ($data_source_ids as $data_source_id) {
        $type_info = explode(':', $data_source_id);

        if ($type_info[0] === 'entity') {
          $view_modes = $display_repository->getViewModes($type_info[1]);

          foreach ($view_modes as $view_mode_id => $view_mode) {
            if ($view_mode['status']) {
              $this->viewModes[$index_id][$view_mode_id] = $view_mode['label'];
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    parent::blockForm($form, $form_state);

    // Form should be pre-filled with existing configuration.
    $config = $this->getConfiguration();
    $custom_list_config = $config['custom_list_config'] ?: [];

    // Sub-form will be created for custom list form.
    $custom_list_config_form = [];

    $list_of_indexes = $this->getListOfIndexes();
    $select_index = $custom_list_config['index'] ?: key($list_of_indexes);
    $custom_list_config_form['search_index'] = [
      '#type' => 'select',
      '#title' => $this->t('Search API Index'),
      '#options' => $list_of_indexes,
      '#default_value' => $select_index,
    ];

    // TODO: has to be fetched over Ajax!!!
    $custom_list_config_form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => $this->getListOfViewModes($select_index),
      '#default_value' => $custom_list_config['view_mode'] ?: '',
    ];

    // Number of elements that will be displayed.
    $custom_list_config_form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#default_value' => $custom_list_config['limit'] ?: 5,
    ];

    $form['custom_list_config_form'] = $custom_list_config_form;

    return $form;
  }

  /**
   * Get available selection list for content entities.
   *
   * @return array
   *   List for selection drop-down box.
   */
  protected function getListOfIndexes() {
    $index_options = [];

    foreach ($this->allowedIndexes as $index_id => $index_label) {
      $index_options[$index_id] = $index_label;
    }

    return $index_options;
  }

  /**
   * Get list of available view modes for search API index.
   *
   * @param string $index_id
   *   Search API index ID.
   *
   * @return array
   *   Returns list of available view modes for Search API index.
   */
  protected function getListOfViewModes($index_id) {
    return $this->viewModes[$index_id];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $custom_list_config = $form_state->getValue('custom_list_config_form');

    $config = $this->getConfiguration();
    $config['custom_list_config'] = $custom_list_config;
    $config['search_api_config'] = ['index' => 'content'];
    $config['inserts'] = $this->getInsertionConfig();

    $this->setConfiguration($config);
  }

  /**
   * Get default view configuration.
   *
   * TODO: There are exceptions, that stored view result cannot be loaded.
   * TODO: That should be investigated.
   *
   * @return array
   *   Returns view configuration.
   */
  protected function getViewConfig() {
    $config = $this->getConfiguration();
    $custom_list_config = $config['custom_list_config'];

    $view_config = [
      'base_table' => 'search_api_index_' . $custom_list_config['search_index'],
      'base_field' => 'search_api_id',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'id' => 'default',
          'position' => 0,
          'display_options' => [
            'access' => [
              'type' => 'none',
              'options' => [],
            ],
            'cache' => [
              'type' => 'tag',
              'options' => [],
            ],
            'query' => [
              'type' => 'views_query',
              'options' => [
                'bypass_access' => FALSE,
                'skip_access' => FALSE,
              ],
            ],
            'pager' => [
              'type' => 'some',
              'options' => [
                'items_per_page' => $custom_list_config['limit'],
                'offset' => 0,
              ],
            ],
            'style' => [
              'type' => 'custom_list_search_api',
              'options' => [
                'inserts' => $config['inserts'],
              ],
            ],
            'row' => [
              'type' => 'search_api',
              'options' => [
                'view_modes' => [
                  // TODO: Get entity:bundle in some way! Look at ::init()
                  // TODO: then getDatasourceIds().
                  'entity:node' => [
                    'article' => $custom_list_config['view_mode'],
                  ],
                ],
              ],
            ],
            'filters' => [],
            'sorts' => [],
            'header' => [],
            'footer' => [],
            'empty' => [],
            'relationships' => [],
            'arguments' => [],
            'display_extenders' => [],
          ],
        ],
        'custom_list_block' => [
          'display_plugin' => 'block',
          'id' => 'custom_list_block',
          'position' => 1,
          'display_options' => [
            'display_extenders' => [],
          ],
          'cache_metadata' => [
            'max-age' => -1,
            'contexts' => [
              'languages:language_interface',
              'user.permissions',
            ],
            'tags' => [],
          ],
        ],
      ],
    ];

    return $view_config;
  }

}
