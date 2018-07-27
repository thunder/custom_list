<?php

namespace Drupal\custom_list\Plugin\views\style;

use Drupal\Core\Entity\EntityInterface;
use Drupal\custom_list\UniqueEntitiesStorageInterface;
use Drupal\views\Entity\View;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base view style for custom list.
 *
 * Displays rows one after another with inserts of manually picked entities or
 * blocks.
 */
abstract class CustomListBase extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Insert configuration for custom list view style.
   *
   * @var array
   *   List of configurations for inserts.
   */
  protected $insertConfiguration = [];

  /**
   * Keeps list of entities that will be used for inserts.
   *
   * @var array
   */
  protected $insertEntities = NULL;

  /**
   * Flag is unique entities should be used or not.
   *
   * @var bool
   */
  protected $uniqueEntities = TRUE;

  /**
   * Unique storage service.
   *
   * @var \Drupal\custom_list\UniqueEntitiesStorageInterface
   */
  protected $uniqueStorage;

  /**
   * Constructs a custom list views style plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\custom_list\UniqueEntitiesStorageInterface $unique_storage
   *   Unique storage service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UniqueEntitiesStorageInterface $unique_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->uniqueStorage = $unique_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('custom_list.unique_entities_store')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // We have to ensure that init is for rendering of view and not internal
    // build, because in case of internal build some required components are
    // not initialized in a way we require them.
    if (!$view->dom_id && !$view->inited) {
      // TODO: This has to be better!
      return;
    }

    // Apply entity or block insertions if there are any.
    if (!empty($options)) {
      if (isset($options['insertions'])) {
        $this->setInsertConfiguration($options['insertions']);
      }

      if (isset($options['unique_entities'])) {
        $this->setUniqueEntities($options['unique_entities']);
      }
    }

    // We need entities for generating filter.
    $insert_entities = $this->getInsertEntities();

    $existing_entities = [];
    if ($this->usesUniqueEntities()) {
      $existing_entities = $this->uniqueStorage->getIds();

      if (!empty($insert_entities)) {
        $this->uniqueStorage->setIds($view->storage->get('base_table'), $view->storage->get('base_field'), $insert_entities);
      }
    }

    // Inserted entities and also already displayed entities by other views
    // should be filtered.
    $existing_entities = array_merge($insert_entities, $existing_entities);
    if (!empty($existing_entities)) {
      $filter_info = $this->getFilter($view->storage, $existing_entities);

      $handler = Views::handlerManager('filter')->getHandler($filter_info);
      $handler->init($view, $display, $filter_info);

      $view->filter[$filter_info['id']] = &$handler;

      // Re-build all the filters.
      if ($view->query) {
        $view->_build('filter');
      }
    }
  }

  /**
   * Get filter definition for custom list.
   *
   * @param \Drupal\views\Entity\View $storage
   *   View storage.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Entity that will be rendered.
   *
   * @return array
   *   Returns filter configuration.
   */
  abstract protected function getFilter(View $storage, array $entities);

  /**
   * Overwrite of parent rendering method in order to insert rows.
   *
   * @param mixed $sets
   *   An array keyed by group content containing the grouping sets to render.
   *   Each set contains the following associative array:
   *   - group: The group content.
   *   - level: The hierarchical level of the grouping.
   *   - rows: The result rows to be rendered in this group..
   *
   * @return array
   *   Render array of grouping sets.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function renderGroupingSets($sets) {
    $output = parent::renderGroupingSets($sets);

    $rendered_insertions = $this->getRenderedInserts();

    $global_offset = 0;
    $output_index = 0;
    $total_outputs = count($output);
    foreach ($rendered_insertions as $insert_index => $rendered_insert) {
      for ($index = $output_index; $index < $total_outputs; $index++) {
        $inner_index = $insert_index - $global_offset;

        if ($inner_index <= count($output[$index]['#rows'])) {
          array_splice($output[$index]['#rows'], $inner_index, 0, [$rendered_insert]);
        }
        else {
          $output_index++;
          $global_offset += count($output[$index]['#rows']);
        }
      }
    }

    return $output;
  }

  /**
   * Get rendered insert rows.
   *
   * @return array
   *   Insert rows with position index.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getRenderedInserts() {
    // TODO: Add some grouping so that preRender is more efficient.
    $insert_entities = $this->getInsertEntities();

    $rendered_insertions = [];
    $insertions = $this->getInsertConfiguration();
    foreach ($insertions as $insert_entry) {
      $config = $insert_entry['config'];

      if ($insert_entry['type'] === 'entity') {
        $entity = $insert_entities[$insert_entry['position']];

        $temporally_result_row = new ResultRow([
          '_entity' => $entity,
          '_relationship_entities' => [],
        ]);

        // Render entity.
        $base_plugin = $this->view->rowPlugin;

        $this->view->rowPlugin = $this->getEntityRowPlugin($entity);
        $this->view->rowPlugin->options['view_mode'] = $config['view_mode'];
        $this->preRender([$temporally_result_row]);

        // Pre-render function adds view property to entity. Since entity is
        // part of result and result is serialized, that leads to broken
        // serialized result, because view is not completely initialized for
        // Search API integration.
        $entity->view = NULL;

        $rendered_insertions[$insert_entry['position']] = $this->view->rowPlugin->render($temporally_result_row);

        $this->view->rowPlugin = $base_plugin;
      }
      elseif ($insert_entry['type'] === 'block') {
        $rendered_insertions[$insert_entry['position']] = $this->getRenderedBlock(
          $config['type'],
          $config['config']
        );
      }
      else {
        throw new \RuntimeException('Unsupported custom list insert type: ' . $insert_entry['type']);
      }
    }

    ksort($rendered_insertions);

    return $rendered_insertions;
  }

  /**
   * Get list of entities for insertion.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   List of entities with correct position.
   */
  protected function getInsertEntities() {
    if ($this->insertEntities === NULL) {
      $this->insertEntities = [];

      /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
      $entity_type_manager = \Drupal::service('entity_type.manager');

      $insertions = $this->getInsertConfiguration();
      foreach ($insertions as $insert_entry) {
        if ($insert_entry['type'] === 'entity') {
          $config = $insert_entry['config'];

          try {
            $this->insertEntities[$insert_entry['position']] = $entity_type_manager->getStorage($config['type'])
              ->load($config['id']);
          }
          catch (\Exception $e) {
            // TODO: Revisit!
            continue;
          }
        }
      }

      ksort($this->insertEntities);
    }

    return $this->insertEntities;
  }

  /**
   * Get row plugin responsible for entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity used to determine row plugin.
   *
   * @return \Drupal\views\Plugin\views\row\RowPluginBase
   *   Row plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getEntityRowPlugin(EntityInterface $entity) {
    $display = $this->view->getDisplay();

    /** @var \Drupal\views\Plugin\views\row\RowPluginBase $plugin */
    $plugin = Views::pluginManager('row')
      ->createInstance('entity:' . $entity->getEntityTypeId());
    $plugin->init($this->view, $display, $display->options['options']);

    return $plugin;
  }

  /**
   * Render block.
   *
   * @param string $blockType
   *   Block type ID.
   * @param array $config
   *   Configuration for block.
   *
   * @return array
   *   Return render array for block.
   */
  protected function getRenderedBlock($blockType, array $config) {
    $block_manager = \Drupal::service('plugin.manager.block');

    /** @var \Drupal\Core\Block\BlockPluginInterface $plugin_block */
    $plugin_block = $block_manager->createInstance($blockType, $config);

    // Some blocks might implement access check.
    $access_result = $plugin_block->access(\Drupal::currentUser());

    // Return empty render array if user doesn't have access. $access_result can
    // be boolean or an AccessResult class.
    if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
      return [];
    }
    $render = $plugin_block->build();

    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();

    $num_of_insertions = count($this->getInsertConfiguration());
    if ($num_of_insertions > 0 && empty($this->view->pager)) {
      $this->view->initPager();

      $this->view->pager->setItemsPerPage($this->view->pager->getItemsPerPage() - $num_of_insertions);
    }
  }

  /**
   * Get insert configuration for custom list style.
   *
   * @return array
   *   Returns insert configuration for custom list style.
   */
  public function getInsertConfiguration() {
    return $this->insertConfiguration;
  }

  /**
   * Set insert configuration for custom list style.
   *
   * @param array $insert_configuration
   *   Insert configuration for custom list style.
   */
  public function setInsertConfiguration(array $insert_configuration) {
    $this->insertConfiguration = $insert_configuration;
  }

  /**
   * Set flag for using unique entities.
   *
   * @param bool $uniqueEntities
   *   The flag value.
   */
  public function setUniqueEntities($uniqueEntities) {
    $this->uniqueEntities = (bool) $uniqueEntities;
  }

  /**
   * Flag if style uses unique entities.
   *
   * @return bool
   *   Returns TRUE if style uses unique entities.
   */
  public function usesUniqueEntities() {
    return $this->uniqueEntities;
  }

}
