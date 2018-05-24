<?php

namespace Drupal\custom_list_default\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_list\Plugin\Block\CustomListBase;

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

    $custom_list_config_form['content'] = [
      '#type' => 'select',
      '#title' => $this->t('Content'),
      '#options' => $this->getContentOptions(),
      '#default_value' => (!empty($custom_list_config['content'])) ? $custom_list_config['content'] : 'node:article',
    ];

    // TODO: has to be fetched over Ajax!!!
    $custom_list_config_form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => [
        'default' => $this->t('Default'),
        'teaser' => $this->t('Teaser'),
      ],
      '#default_value' => (!empty($custom_list_config['view_mode'])) ? $custom_list_config['view_mode'] : 'teaser',
    ];

    // TODO: Sorting options should be more powerful!
    $custom_list_config_form['sort'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort'),
      '#options' => [
        'DESC' => $this->t('DESC'),
        'ASC' => $this->t('ASC'),
      ],
      '#default_value' => (!empty($custom_list_config['sort'])) ? $custom_list_config['sort'] : 'DESC',
    ];

    // Number of elements that will be displayed.
    $custom_list_config_form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#default_value' => (!empty($custom_list_config['limit'])) ? $custom_list_config['limit'] : 5,
    ];

    $custom_list_config_form['insertion_form'] = $this->getInsertsForm((!empty($config['inserts'])) ? $config['inserts'] : []);
    $form['custom_list_config_form'] = $custom_list_config_form;

    return $form;
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
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $custom_list_config = $form_state->getValue('custom_list_config_form');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::entityTypeManager();
    $content_info = explode(':', $custom_list_config['content']);
    $type_info = $entity_type_manager->getDefinition($content_info[0]);

    $entity_type_config = [];
    $entity_type_config['base_table'] = $type_info->getBaseTable();
    $entity_type_config['data_table'] = $type_info->getDataTable();
    $entity_keys = $type_info->getKeys();

    if (isset($entity_keys['id'])) {
      $entity_type_config['base_field'] = $entity_keys['id'];
      $entity_type_config['type_field'] = $entity_keys['bundle'];
    }

    $config = $this->getConfiguration();
    $config['custom_list_config'] = $custom_list_config;
    $config['entity_type_config'] = $entity_type_config;

    $config['inserts'] = $this->fetchInsertSelection($custom_list_config['insertion_form']);

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
              ],
            ],
            "row" => [
              "type" => "entity:" . $content_info[0],
              "options" => [
                "view_mode" => $custom_list_config['view_mode'],
              ],
            ],
            // TODO: Sorts should be fully defined by UI.
            "sorts" => [
              "created" => [
                "id" => "created",
                "table" => $entity_type_config['data_table'],
                "field" => "created",
                "order" => $custom_list_config['sort'],
                "entity_type" => $content_info[0],
                "entity_field" => "created",
                "plugin_id" => "date",
                "relationship" => "none",
                "group_type" => "group",
                "admin_label" => "",
                "exposed" => FALSE,
                "expose" => [
                  "label" => "",
                ],
                "granularity" => "second",
              ],
            ],
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

    return $view_config;
  }

}
