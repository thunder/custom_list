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
   * Temporally store for entity type info in case of frequent fetching.
   *
   * @var array
   */
  protected $entityTypeInfo = NULL;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityTypeManager;

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
   * Get the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager() {
    if (empty($this->entityTypeManager)) {
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }

    return $this->entityTypeManager;
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

    // TODO: Add search text.
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
    if ($this->entityTypeInfo !== NULL) {
      return $this->entityTypeInfo;
    }

    $this->entityTypeInfo = [];

    $index = Index::load($this->configuration['search_index']);
    $data_sources = $index->getDatasources();
    /** @var \Drupal\search_api\Datasource\DatasourceInterface $data_source */
    foreach ($data_sources as $data_source_id => $data_source) {
      $content_info = explode(':', $data_source_id);

      if ($content_info[0] === 'entity') {
        $bundles = $data_source->getBundles();
        foreach ($bundles as $bundle_id => $bundle) {
          $this->entityTypeInfo[] = [
            'entity_type' => $content_info[1],
            'bundle' => $bundle_id,
          ];
        }
      }
    }

    return $this->entityTypeInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function generateConfiguration($consumer_type, array $custom_list_config) {
    $view_config = $this->getBaseViewConfig($custom_list_config);
    if ($consumer_type === 'entity_browser_view') {
      $this->addEntityBrowserDisplay($view_config);
    }

    return $view_config;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedConsumers() {
    return [
      'view',
      'entity_browser_view',
    ];
  }

  /**
   * Get the base view configuration for this plugin.
   *
   * TODO: Heavy revisit and cleanup, how view config is generated.
   *
   * @param array $custom_list_config
   *   Configuration for custom list.
   *
   * @return array
   *   Returns the base view configuration.
   */
  protected function getBaseViewConfig(array $custom_list_config) {
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
                "default_row_class" => TRUE,
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

    $view_modes = &$view_config['display']['default']['display_options']['row']['options']['view_modes'];
    foreach ($entity_type_infos as $entity_type_info) {
      $entity_type_key = 'entity:' . $entity_type_info['entity_type'];
      if (!isset($view_modes[$entity_type_key])) {
        $view_modes[$entity_type_key] = [];
      }

      $view_modes[$entity_type_key][$entity_type_info['bundle']] = $custom_list_config['view_mode'];
    }

    return $view_config;
  }

  /**
   * Append entity browser display to the base view configuration.
   */
  protected function addEntityBrowserDisplay(array &$view_config) {
    $base_filter = $view_config['display']['default']['display_options']['filters'];

    $config = $this->configuration;

    // Get first entity type, so that we can display title for it.
    // TODO: add support for multiple entity types.
    $entity_type_infos = $this->getEntityTypeInfo();
    $entity_type_id = '';
    $entity_type_label_field = 'lablel';
    foreach ($entity_type_infos as $entity_type_info) {
      $entity_type_id = $entity_type_info['entity_type'];

      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $entity_type_label_field = $entity_type->getKeys()['label'];

      break;
    }

    $base_filter += [
      $entity_type_label_field => [
        'id' => $entity_type_label_field,
        'plugin_id' => 'search_api_text',
        'table' => 'search_api_index_' . $config['search_index'],
        'field' => $entity_type_label_field,
        'relationship' => 'none',
        'group_type' => 'group',
        'group' => 1,
        'admin_label' => '',
        'operator' => '=',
        'value' => [
          'min' => '',
          'max' => '',
          'value' => '',
        ],
        'exposed' => TRUE,
        'expose' => [
          'operator_id' => 'title_op',
          'label' => 'Title',
          'description' => '',
          'use_operator' => FALSE,
          'operator' => 'title_op',
          'identifier' => $entity_type_label_field,
          'required' => FALSE,
          'remember' => FALSE,
          'multiple' => FALSE,
          'remember_roles' =>
            [
              'authenticated' => 'authenticated',
              'anonymous' => '0',
              'administrator' => '0',
            ],
          'placeholder' => '',
        ],
      ],
    ];

    $view_config['display']['source_list_display'] = [
      'display_plugin' => 'source_list_display',
      'id' => 'source_list_display',
      'position' => 2,
      'display_options' => [
        'display_extenders' => [],
        'defaults' => [
          'style' => FALSE,
          'row' => FALSE,
          'fields' => FALSE,
          'pager' => FALSE,
          'filters' => FALSE,
        ],
        "row" => [
          "type" => "fields",
        ],
        'filters' => $base_filter,
        'style' => [
          'type' => 'table',
          'options' => [
            'grouping' => [],
            'row_class' => '',
            'default_row_class' => TRUE,
            'override' => TRUE,
            'sticky' => FALSE,
            'caption' => '',
            'summary' => '',
            'description' => '',
            'columns' => [
              'entity_browser_select' => 'entity_browser_select',
              $entity_type_label_field => $entity_type_label_field,
            ],
            'info' => [
              'entity_browser_select' => [
                'align' => '',
                'separator' => '',
                'empty_column' => FALSE,
                'responsive' => '',
              ],
              $entity_type_label_field => [
                'sortable' => FALSE,
                'default_sort_order' => 'asc',
                'align' => '',
                'separator' => '',
                'empty_column' => FALSE,
                'responsive' => '',
              ],
            ],
            'default' => '-1',
            'empty_table' => FALSE,
          ],
        ],
        'fields' => [
          'entity_browser_select' => [
            'id' => 'entity_browser_select',
            'plugin_id' => 'entity_browser_search_api_select',
            'table' => 'search_api_index_' . $config['search_index'],
            'field' => 'entity_browser_select',
            'label' => 'Select',
          ],
          $entity_type_label_field => [
            'id' => $entity_type_label_field,
            'table' => 'search_api_datasource_' . $config['search_index'] . '_entity_' . $entity_type_id,
            'entity_type' => $entity_type_id,
            'plugin_id' => 'search_api_field',
            'field' => $entity_type_label_field,
            'relationship' => 'none',
            'group_type' => 'group',
            'label' => 'Title',
            'type' => 'string',
            'settings' => [
              'link_to_entity' => FALSE,
            ],
            'field_rendering' => TRUE,
            'fallback_handler' => 'search_api',
            'fallback_options' => [
              'link_to_item' => FALSE,
              'use_highlighting' => FALSE,
            ],
          ],
        ],
        'pager' => [
          'type' => 'full',
          'options' => [
            'items_per_page' => 5,
            'offset' => 0,
            'id' => 0,
            'total_pages' => NULL,
            'expose' => [
              'items_per_page' => FALSE,
              'items_per_page_label' => 'Items per page',
              'items_per_page_options' => '5, 10, 25, 50',
              'items_per_page_options_all' => FALSE,
              'items_per_page_options_all_label' => '- All -',
              'offset' => FALSE,
              'offset_label' => 'Offset',
            ],
            'tags' => [
              'previous' => '‹ Previous',
              'next' => 'Next ›',
              'first' => '« First',
              'last' => 'Last »',
            ],
          ],
        ],
      ],
    ];
  }

}
