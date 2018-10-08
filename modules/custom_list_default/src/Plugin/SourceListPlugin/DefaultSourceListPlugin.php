<?php

namespace Drupal\custom_list_default\Plugin\SourceListPlugin;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_list\Plugin\SourceListPluginBase;
use Drupal\views\Views;

/**
 * Default source list plugin.
 *
 * @SourceListPlugin(
 *  id = "source_list_plugin_default",
 *  label = @Translation("Default source list"),
 * )
 */
class DefaultSourceListPlugin extends SourceListPluginBase {

  /**
   * List of supported entity types.
   *
   * TODO: It would be nice to find some automated way for list of entity types.
   *
   * @var array
   */
  protected static $supportedEntityTypes = [
    'node',
    'media',
  ];

  /**
   * Keeps views data information per table.
   *
   * @var array
   */
  protected $viewsData = [];

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form, FormStateInterface $form_state) {
    // Sub-form will be created for custom list form.
    $custom_list_config_form = [];

    // Get pre-selections.
    $preselected_content_type = (!empty($this->configuration['content_type'])) ? $this->configuration['content_type'] : 'node:article';
    if ($form_state->getValue('content_type')) {
      $preselected_content_type = $form_state->getValue('content_type');
    }

    $preselected_sorts = (!empty($this->configuration['sort_selection'])) ? $this->configuration['sort_selection'] : [];
    $preselected_filters = (!empty($this->configuration['filter_selection'])) ? $this->configuration['filter_selection'] : [];

    // Get all available options.
    $options = [
      'content_type' => $this->getContentOptions(),
      'sort' => $this->getSortOptions($preselected_content_type),
      'filter' => $this->getFilterOptions($preselected_content_type),
    ];

    $custom_list_config_form['options'] = [
      '#type' => 'hidden',
      '#value' => json_encode($options),
      '#attributes' => [
        'class' => ['custom-list-default__default-source-list-plugin__options'],
      ],
    ];

    $custom_list_config_form['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content'),
      '#options' => $options['content_type'],
      '#default_value' => $preselected_content_type,
      '#ajax' => [
        'callback' => [$this, 'onContentTypeChange'],
      ],
    ];

    $custom_list_config_form['sort_selection'] = [
      '#type' => 'hidden',
      '#default_value' => json_encode($preselected_sorts),
      '#attributes' => [
        'class' => [
          'custom-list-default__default-source-list-plugin__sort_selection',
        ],
      ],
      '#attached' => [
        'library' => [
          'custom_list_default/sort_selector',
        ],
      ],
    ];

    $custom_list_config_form['filter_selection'] = [
      '#type' => 'hidden',
      '#default_value' => json_encode($preselected_filters),
      '#attributes' => [
        'class' => [
          'custom-list-default__default-source-list-plugin__filter_selection',
        ],
      ],
      '#attached' => [
        'library' => [
          'custom_list_default/filter_selector',
        ],
      ],
    ];

    return $custom_list_config_form;
  }

  /**
   * Handles switching of the content type.
   */
  public function onContentTypeChange($form, FormStateInterface $form_state) {
    $result = new AjaxResponse();

    $result->addCommand(new ReplaceCommand('.custom-list-default__default-source-list-plugin__options', $form['plugin_subform']['options']));

    return $result;
  }

  /**
   * Get available selection list for content entities.
   *
   * @return array
   *   List for selection drop-down box.
   */
  protected function getContentOptions() {
    $content_options = [];

    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info_service */
    $bundle_info_service = \Drupal::service('entity_type.bundle.info');

    $bundles = $bundle_info_service->getAllBundleInfo();
    foreach ($bundles as $entity_type_id => $bundle_infos) {
      if (in_array($entity_type_id, static::$supportedEntityTypes)) {
        foreach ($bundle_infos as $bundle_id => $bundle_info) {
          $content_options[$entity_type_id . ':' . $bundle_id] = $bundle_info['label'];
        }
      }
    }

    return $content_options;
  }

  /**
   * Get content type info.
   *
   * @param string $content_type
   *   Content type in format of entity_type_id:bundle.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   Returns entity type.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getTypeInfo($content_type) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::entityTypeManager();
    $content_info = explode(':', $content_type);

    return $entity_type_manager->getDefinition($content_info[0]);
  }

  /**
   * Get sorting options.
   *
   * @param string $content_type
   *   Content type in format of entity_type_id:bundle.
   *
   * @return array
   *   Return sorting options.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getSortOptions($content_type) {
    $type_info = $this->getTypeInfo($content_type);

    $data_table = $type_info->getDataTable();
    $options = Views::viewsDataHelper()->fetchFields([$data_table], 'sort');

    $sort_options = [];
    foreach ($options as $option_id => $option) {
      if (strpos($option_id, $data_table . '.') === 0) {
        $sort_options[$option_id] = strval($option['title']);
      }
    }

    return $sort_options;
  }

  /**
   * Get filtering options.
   *
   * @param string $content_type
   *   Content type in format of entity_type_id:bundle.
   *
   * @return array
   *   Return filtering options.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getFilterOptions($content_type) {
    $type_info = $this->getTypeInfo($content_type);

    $data_table = $type_info->getDataTable();
    $options = Views::viewsDataHelper()->fetchFields([$data_table], 'filter');

    /** @var \Drupal\custom_list_default\FilterFormResolverInterface $filter_form_resolver */
    $filter_form_resolver = \Drupal::service('custom_list_default.filter_form_resolver');

    // Status, bundle and custom list filters are programmatically added.
    $skip_fields = [
      'custom_list_default',
      $type_info->getKey('status'),
      $type_info->getKey('bundle'),
    ];

    $filter_options = [];
    foreach ($options as $option_id => $option) {
      if (strpos($option_id, $data_table . '.') !== 0) {
        continue;
      }

      $field_info = explode('.', $option_id);
      if (in_array($field_info[1], $skip_fields)) {
        continue;
      }

      $filter_options[$option_id] = $this->getFilterOption($field_info[0], $field_info[1]);

      $handler = $this->getFilterHandler($filter_options[$option_id]);
      $filter_options[$option_id]['operators'] = $handler->operatorOptions();

      $filter_options[$option_id]['title'] = isset($handler->definition['title']) ? $handler->definition['title'] : $option_id;
      $filter_options[$option_id]['form_info'] = $filter_form_resolver->getFormInfo($handler);
    }

    return $filter_options;
  }

  /**
   * Get views data for table.
   *
   * @param string $table
   *   The table name.
   *
   * @return array
   *   Returns data for the table.
   */
  protected function getViewsData($table) {
    if (!isset($this->viewsData[$table])) {
      $this->viewsData[$table] = Views::viewsData()->get($table);
    }

    return $this->viewsData[$table];
  }

  /**
   * Get information for single filter.
   *
   * @param string $table
   *   The name of the table for filter.
   * @param string $field
   *   The name of the field for filter.
   *
   * @return array
   *   Returns filter options.
   */
  public function getFilterOption($table, $field) {
    $data = $this->getViewsData($table);

    // Base filter configuration.
    $filter_config = [
      'id' => $table . '.' . $field,
      'table' => $table,
      'field' => $field,
    ];

    if (isset($data['table']['entity type'])) {
      $filter_config['entity_type'] = $data['table']['entity type'];
    }
    if (isset($data[$field]['entity field'])) {
      $filter_config['entity_field'] = $data[$field]['entity field'];
    }

    // Load the plugin ID if available.
    if (isset($data[$field]['filter']['id'])) {
      $filter_config['plugin_id'] = $data[$field]['filter']['id'];
    }

    return $filter_config;
  }

  /**
   * Get filter configuration.
   *
   * @param array $filter_info
   *   Filter info.
   *
   * @return \Drupal\views\Plugin\views\filter\FilterPluginBase
   *   Returns filter handler.
   */
  public function getFilterHandler(array $filter_info) {
    return Views::handlerManager('filter')->getHandler($filter_info, []);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormData(array $form, FormStateInterface $form_state) {
    $source_list_plugin_config = [];

    unset($source_list_plugin_config['options']);

    // Fetch content type information.
    $source_list_plugin_config['content_type'] = $form_state->getValue('content_type');
    $content_type = $this->getTypeInfo($source_list_plugin_config['content_type']);

    $source_list_plugin_config['base_table'] = $content_type->getBaseTable();
    $source_list_plugin_config['data_table'] = $content_type->getDataTable();
    $entity_keys = $content_type->getKeys();

    if (isset($entity_keys['id'])) {
      $source_list_plugin_config['base_field'] = $entity_keys['id'];
      $source_list_plugin_config['type_field'] = $entity_keys['bundle'];
    }

    // Fetch sort selection.
    $source_list_plugin_config['sort_selection'] = json_decode($form_state->getValue('sort_selection'), TRUE);
    $source_list_plugin_config['filter_selection'] = json_decode($form_state->getValue('filter_selection'), TRUE);

    return $source_list_plugin_config;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeInfo() {
    $content_info = explode(':', $this->configuration['content_type']);

    return [
      [
        'entity_type' => $content_info[0],
        'bundle' => $content_info[1],
      ],
    ];
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
    $content_info = explode(':', $config['content_type']);

    $view_config = [
      "base_table" => $config['data_table'],
      "base_field" => $config['base_field'],
      "display" => [
        "default" => [
          "display_plugin" => "default",
          "id" => "default",
          "position" => 0,
          "display_options" => [
            "access" => [
              "type" => "perm",
              "options" => [
                "perm" => "access content",
              ],
            ],
            "cache" => [
              "type" => "tag",
              "options" => [],
            ],
            "query" => [
              "type" => "views_query",
              "options" => [
                "disable_sql_rewrite" => FALSE,
                "distinct" => FALSE,
                "replica" => FALSE,
                "query_comment" => "",
                "query_tags" => [],
              ],
            ],
            "pager" => [
              "type" => "some",
              "options" => [
                "items_per_page" => $custom_list_config['limit'],
                "offset" => 0,
              ],
            ],
            "style" => [
              "type" => "custom_list_default",
              "options" => [
                "insertions" => $custom_list_config['insertions'],
                "unique_entities" => $custom_list_config['unique_entities'],
              ],
            ],
            "row" => [
              "type" => "entity:" . $content_info[0],
              "options" => [
                "view_mode" => $custom_list_config['view_mode'],
              ],
            ],
            "filters" => [
              "type" => [
                "id" => "type",
                "table" => $config['data_table'],
                "field" => $config['type_field'],
                "value" => [
                  $content_info[1] => $content_info[1],
                ],
                "entity_type" => $content_info[0],
                "entity_field" => $config['type_field'],
                "plugin_id" => "bundle",
              ],
              "status" => [
                "value" => "1",
                "table" => $config['data_table'],
                "field" => "status",
                "plugin_id" => "boolean",
                "entity_type" => $content_info[0],
                "entity_field" => "status",
                "id" => "status",
                "expose" => [
                  "operator" => "",
                ],
                "group" => 1,
              ],
            ],
            "filter_groups" => [
              "operator" => "AND",
              "groups" => [
                "1" => "AND",
              ],
            ],
            "sorts" => [],
            "header" => [],
            "footer" => [],
            "empty" => [],
            "relationships" => [],
            "arguments" => [],
            "display_extenders" => [],
          ],
        ],
        "custom_list_block" => [
          "display_plugin" => "block",
          "id" => "custom_list_block",
          "position" => 1,
          "display_options" => [
            "defaults" => [
              "filters" => TRUE,
              "filter_groups" => TRUE,
            ],
          ],
          "cache_metadata" => [
            "max-age" => -1,
            "contexts" => [
              "languages:language_interface",
              "user.permissions",
            ],
            "tags" => [],
          ],
        ],
      ],
    ];

    // Add sorts.
    $sort_selection = isset($config['sort_selection']) ? $config['sort_selection'] : [];
    foreach ($sort_selection as $sort_info) {
      $this->appendSortOption($view_config['display']['default']['display_options']['sorts'], $sort_info, $content_info[0]);
    }

    // Add filters.
    $filter_selection = isset($config['filter_selection']) ? $config['filter_selection'] : [];
    foreach ($filter_selection as $filter_info) {
      $this->appendFilterOption($view_config['display']['default']['display_options']['filters'], $filter_info);
    }

    return $view_config;
  }

  /**
   * Append entity browser display to the base view configuration.
   */
  protected function addEntityBrowserDisplay(array &$view_config) {
    $base_filter = $view_config['display']['default']['display_options']['filters'];

    $source_list_config = $this->configuration;
    $content_info = explode(':', $source_list_config['content_type']);

    $type_info = $this->getTypeInfo($source_list_config['content_type']);
    $title_field_name = $type_info->getKeys()['label'];

    $base_filter += [
      $title_field_name => [
        'id' => $title_field_name,
        'table' => $view_config['base_table'],
        'entity_type' => $content_info[0],
        'field' => $title_field_name,
        'entity_field' => $title_field_name,
        'plugin_id' => 'string',
        'relationship' => 'none',
        'group_type' => 'group',
        'admin_label' => '',
        'operator' => 'contains',
        'value' => '',
        'group' => 1,
        'exposed' => TRUE,
        'expose' => [
          'operator_id' => 'title_op',
          'label' => 'Title',
          'use_operator' => FALSE,
          'operator' => 'title_op',
          'identifier' => $title_field_name,
          'required' => FALSE,
          'remember' => FALSE,
          'multiple' => FALSE,
          'remember_roles' => [
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
        'defaults' => [
          'style' => FALSE,
          'row' => FALSE,
          'fields' => FALSE,
          'pager' => FALSE,
          'filters' => FALSE,
        ],
        'style' => [
          'type' => 'table',
          'options' => [
            'grouping' => [],
            'default_row_class' => TRUE,
            'override' => TRUE,
            'sticky' => FALSE,
            'columns' => [
              'entity_browser_select' => 'entity_browser_select',
              $title_field_name => $title_field_name,
            ],
            'info' => [
              'entity_browser_select' => [
                'align' => '',
                'separator' => '',
                'empty_column' => FALSE,
                'responsive' => '',
              ],
              $title_field_name => [
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
        'row' => [
          'type' => 'fields',
          'options' => [
            'default_field_elements' => TRUE,
            'inline' => [],
            'separator' => '',
            'hide_empty' => FALSE,
          ],
        ],
        'filters' => $base_filter,
        'fields' => [
          'entity_browser_select' => [
            'id' => 'entity_browser_select',
            'table' => $content_info[0],
            'entity_type' => $content_info[0],
            'field' => 'entity_browser_select',
            'label' => 'Select',
            'plugin_id' => 'entity_browser_select',
          ],
          $title_field_name => [
            'id' => $title_field_name,
            'type' => 'string',
            'table' => $view_config['base_table'],
            'entity_type' => $content_info[0],
            'field' => $title_field_name,
            'entity_field' => $title_field_name,
            'label' => 'Title',
            'settings' => [
              'link_to_entity' => FALSE,
            ],
            'plugin_id' => 'field',
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

  /**
   * Append sort option to existing view configuration array.
   *
   * @param array $sorts
   *   Existing list of sorts as reference.
   * @param array $sort_info
   *   Sort info array.
   * @param string $entity_type
   *   Entity type.
   */
  protected function appendSortOption(array &$sorts, array $sort_info, $entity_type) {
    $column_info = explode('.', $sort_info['sort_id']);

    $sorts[$column_info[1]] = [
      "id" => $column_info[1],
      "table" => $column_info[0],
      "field" => $column_info[1],
      "order" => $sort_info['order'],
      "entity_type" => $entity_type,
      "entity_field" => $column_info[1],
      "exposed" => FALSE,
    ];
  }

  /**
   * Append filter option to existing view configuration array.
   *
   * @param array $filters
   *   Existing list of filters as reference.
   * @param array $filter_info
   *   The filter info array.
   */
  protected function appendFilterOption(array &$filters, array $filter_info) {
    $column_info = explode('.', $filter_info['filter_id']);

    $filter = $this->getFilterOption($column_info[0], $column_info[1]);

    // In order to find does filter support array formatted values or only
    // single value, we have to do some unconventional checks.
    $filter['value'] = json_decode($filter_info['value'], TRUE);
    $filter['operator'] = $filter_info['operator'];

    $filters[$filter_info['filter_id']] = $filter;
  }

}
