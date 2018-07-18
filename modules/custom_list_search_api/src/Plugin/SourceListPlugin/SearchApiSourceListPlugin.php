<?php

namespace Drupal\custom_list_search_api\Plugin\SourceListPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_list\Plugin\SourceListPluginBase;
use Drupal\search_api\Entity\Index;

/**
 * Search API source list plugin.
 *
 * @SourceListPlugin(
 *  id = "source_list_plugin_search_api",
 *  label = @Translation("Search API source list"),
 * )
 */
class SearchApiSourceListPlugin extends SourceListPluginBase {

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
    /** @var \Drupal\search_api\Entity\Index $index */
    foreach (Index::loadMultiple() as $index) {
      $index_id = $index->id();

      $this->allowedIndexes[$index_id] = $index->label();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm() {
    // List of available Search API indexes.
    $list_of_indexes = $this->getListOfIndexes();

    // Get pre-selections.
    $select_index = (!empty($this->configuration['index'])) ? $this->configuration['index'] : key($list_of_indexes);

    // Sub-form will be created for custom list form.
    $custom_list_config_form = [];

    $custom_list_config_form['search_index'] = [
      '#type' => 'select',
      '#title' => $this->t('Search API Index'),
      '#options' => $list_of_indexes,
      '#default_value' => $select_index,
    ];

    // TODO: Add search text!
    return $custom_list_config_form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormData(array $form, FormStateInterface $form_state) {
    $source_list_plugin_config = [];

    // Fetch search index ID.
    $source_list_plugin_config['search_index'] = $form_state->getValue('search_index');

    return $source_list_plugin_config;
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
   * {@inheritdoc}
   */
  public function getEntityTypeInfo() {
    $index = Index::load($this->configuration['search_index']);

    $entity_type_infos = [];

    $data_sources = $index->getDatasources();
    /** @var \Drupal\search_api\Datasource\DatasourceInterface $data_source */
    foreach ($data_sources as $data_source_id => $data_source) {
      $content_info = explode(':', $data_source_id);

      if ($content_info[0] === 'entity') {
        $bundles = $data_source->getBundles();
        foreach ($bundles as $bundle_id => $bundle) {
          $entity_type_infos[] = [
            'entity_type' => $content_info[1],
            'bundle' => $bundle_id,
          ];
        }
      }
    }

    return $entity_type_infos;
  }

  /**
   * {@inheritdoc}
   */
  public function generateConfiguration($consumer_type, array $custom_list_config) {
    $config = $this->configuration;

    $entity_type_infos = $this->getEntityTypeInfo();

    $view_config = [
      'base_table' => 'search_api_index_' . $config['search_index'],
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
                'insertions' => $custom_list_config['insertions'],
                'unique_entities' => $custom_list_config['unique_entities'],
              ],
            ],
            'row' => [
              'type' => 'search_api',
              'options' => [
                'view_modes' => [],
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
              "languages:language_content",
              "languages:language_interface",
              "url.query_args",
            ],
            'tags' => [],
          ],
        ],
      ],
    ];

    $view_modes = &$view_config['display']['default']['row']['options']['view_modes'];
    foreach ($entity_type_infos as $entity_type_info) {
      $entity_type_key = 'entity:' . $entity_type_info['entity_type'];
      if (!isset($view_modes[$entity_type_key])) {
        $view_modes[$entity_type_key] = [];
      }

      $view_modes[$entity_type_key][$entity_type_info['bundle']] = $custom_list_config['view_mode'];
    }

    return $view_config;
  }

}
