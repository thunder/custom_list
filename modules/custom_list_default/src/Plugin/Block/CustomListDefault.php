<?php

namespace Drupal\custom_list_default\Plugin\Block;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_list\Plugin\Block\CustomListBase;
use Drupal\views\Views;

/**
 * Provides a block for default custom list.
 *
 * @Block(
 *   id = "custom_list_default",
 *   admin_label = @Translation("Default custom list")
 * )
 */
class CustomListDefault extends CustomListBase {

  /**
   * List of supported entity types.
   *
   * TODO: It would be nice to find some automated way for list of entity types.
   *
   * @var array
   */
  protected $supportedEntityTypes = [
    'node',
    'media',
  ];

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    parent::blockForm($form, $form_state);

    // Form should be pre-filled with existing configuration.
    $config = $this->getConfiguration();
    $custom_list_config = (!empty($config['custom_list_config'])) ? $config['custom_list_config'] : [];

    // Sub-form will be created for custom list form.
    $custom_list_config_form = [];

    // Get pre-selections.
    $preselected_content_type = (!empty($custom_list_config['content'])) ? $custom_list_config['content'] : 'node:article';
    $preselected_view_mode = (!empty($custom_list_config['view_mode'])) ? $custom_list_config['view_mode'] : 'default';
    $preselected_sorts = (!empty($custom_list_config['sort_selection'])) ? $custom_list_config['sort_selection'] : [];
    $preselected_unique_entities = (isset($config['unique_entities'])) ? $config['unique_entities'] : TRUE;

    // Get all available options.
    $options = [
      'content_type' => $this->getContentOptions(),
      'view_mode' => $this->getViewModeList($preselected_content_type),
      'sort' => $this->getSortOptions($preselected_content_type),
    ];

    $custom_list_config_form['options'] = [
      '#type' => 'hidden',
      '#default_value' => json_encode($options),
    ];

    $custom_list_config_form['content'] = [
      '#type' => 'select',
      '#title' => $this->t('Content'),
      '#options' => $options['content_type'],
      '#default_value' => $preselected_content_type,
      '#ajax' => [
        'callback' => [$this, 'onContentChange'],
      ],
    ];

    $custom_list_config_form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => $options['view_mode'],
      '#default_value' => $preselected_view_mode,
    ];

    // Number of elements that will be displayed.
    $custom_list_config_form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#default_value' => (!empty($custom_list_config['limit'])) ? $custom_list_config['limit'] : 5,
    ];

    $custom_list_config_form['unique_form'] = $this->getUniqueSelector($preselected_unique_entities);

    $custom_list_config_form['sort_selection'] = [
      '#type' => 'hidden',
      '#default_value' => json_encode($preselected_sorts),
      '#attached' => [
        'library' => [
          'custom_list/sort_selector',
        ],
      ],
    ];

    $custom_list_config_form['insertion_form'] = $this->getInsertsForm((!empty($config['inserts'])) ? $config['inserts'] : []);
    $form['custom_list_config_form'] = $custom_list_config_form;

    return $form;
  }

  /**
   * Handles switching of the content type.
   */
  public function onContentChange($form, FormStateInterface $form_state) {
    $result = new AjaxResponse();

    $result->addCommand(new ReplaceCommand('.form-item-settings-custom-list-config-form-view-mode', $form['settings']['custom_list_config_form']['view_mode']));

    return $result;
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
   * Get list of view modes for content type.
   *
   * @param string $content_type
   *   Content type in format of entity_type_id:bundle.
   *
   * @return array
   *   Return list of view modes for content type.
   */
  protected function getViewModeList($content_type) {
    $view_mode_list = [
      'default' => $this->t('Default'),
    ];

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $content_info = explode(':', $content_type);
    $view_modes = $display_repository->getViewModes($content_info[0]);

    foreach ($view_modes as $view_mode_id => $view_mode) {
      if ($view_mode['status']) {
        $view_mode_list[$view_mode_id] = $view_mode['label'];
      }
    }

    return $view_mode_list;
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
      if (in_array($entity_type_id, $this->supportedEntityTypes)) {
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
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $custom_list_config = $form_state->getValue('custom_list_config_form');

    $type_info = $this->getTypeInfo($custom_list_config['content']);

    $entity_type_config = [];
    $entity_type_config['base_table'] = $type_info->getBaseTable();
    $entity_type_config['data_table'] = $type_info->getDataTable();
    $entity_keys = $type_info->getKeys();

    if (isset($entity_keys['id'])) {
      $entity_type_config['base_field'] = $entity_keys['id'];
      $entity_type_config['type_field'] = $entity_keys['bundle'];
    }

    $custom_list_config['sort_selection'] = json_decode($custom_list_config['sort_selection'], TRUE);

    $config = $this->getConfiguration();
    $config['custom_list_config'] = $custom_list_config;
    $config['entity_type_config'] = $entity_type_config;

    $config['inserts'] = $this->fetchInsertSelection($custom_list_config['insertion_form']);
    $config['unique_entities'] = $this->fetchUniqueSelector($custom_list_config['unique_form']);

    $this->setConfiguration($config);
  }

  /**
   * Get default view configuration.
   *
   * TODO: Heavy revisit and cleanup, how view config is generated.
   *
   * @return array
   *   Returns view configuration.
   */
  protected function getViewConfig() {
    $config = $this->getConfiguration();
    $custom_list_config = $config['custom_list_config'];
    $entity_type_config = $config['entity_type_config'];

    $content_info = explode(':', $custom_list_config['content']);

    $view_config = [
      "base_table" => $entity_type_config['data_table'],
      "base_field" => $entity_type_config['base_field'],
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
                "inserts" => $config['inserts'],
                "unique_entities" => $config['unique_entities'],
              ],
            ],
            "row" => [
              "type" => "entity:" . $content_info[0],
              "options" => [
                "view_mode" => $custom_list_config['view_mode'],
              ],
            ],
            "sorts" => [],
            "filters" => [],
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
            // TODO: Filters should be fully defined by UI.
            "filters" => [
              "type" => [
                "id" => "type",
                "table" => $entity_type_config['data_table'],
                "field" => $entity_type_config['type_field'],
                "value" => [
                  $content_info[1] => $content_info[1],
                ],
                "entity_type" => $content_info[0],
                "entity_field" => $entity_type_config['type_field'],
                "plugin_id" => "bundle",
              ],
              "status" => [
                "value" => "1",
                "table" => $entity_type_config['data_table'],
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
            "defaults" => [
              "filters" => FALSE,
              "filter_groups" => FALSE,
            ],
            "filter_groups" => [
              "operator" => "AND",
              "groups" => [
                "1" => "AND",
              ],
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
    $sort_selection = isset($custom_list_config['sort_selection']) ? $custom_list_config['sort_selection'] : [];
    foreach ($sort_selection as $sort_info) {
      $this->appendSortOption($view_config['display']['default']['display_options']['sorts'], $sort_info, $content_info[0]);
    }

    return $view_config;
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

}
