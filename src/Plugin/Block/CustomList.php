<?php

namespace Drupal\custom_list\Plugin\Block;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\custom_list\Plugin\SourceListPluginManager;
use Drupal\views\Entity\View;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom list block.
 *
 * @package Drupal\custom_list\Plugin\Block
 *
 * @Block(
 *   id = "custom_list",
 *   admin_label = @Translation("Custom list")
 * )
 */
class CustomList extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Source list plugin manager service.
   *
   * @var \Drupal\custom_list\Plugin\SourceListPluginManager
   */
  protected $sourceListPluginManager;

  /**
   * Display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $displayRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new FieldBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\custom_list\Plugin\SourceListPluginManager $source_list_plugin_manager
   *   The source list plugin manager service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository
   *   The display repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SourceListPluginManager $source_list_plugin_manager, EntityDisplayRepositoryInterface $display_repository, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->sourceListPluginManager = $source_list_plugin_manager;
    $this->displayRepository = $display_repository;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.source_list_plugin'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    parent::blockForm($form, $form_state);

    // Form should be pre-filled with existing configuration.
    $config = $this->getConfiguration();

    // This is specific configuration for custom list. We are using
    // "custom_list" key in order to separate custom list configuration
    // from block configuration.
    $custom_list_config = (!empty($config['custom_list'])) ? $config['custom_list'] : [];

    // Get list of available source lists.
    try {
      $source_lists = $this->getSourceLists();
    }
    catch (\Exception $e) {
      $source_lists = [];
    }

    // We need to use input value to get current selected list in ajax call.
    // TODO: This is strange!
    $input = $form_state->getUserInput();

    // Get pre-selections.
    $source_list = (!empty($input['settings']['custom_list_config_form']['source_list'])) ? $input['settings']['custom_list_config_form']['source_list'] : ((!empty($custom_list_config['source_list'])) ? $custom_list_config['source_list'] : key($source_lists));
    $preselected_view_mode = (!empty($custom_list_config['view_mode'])) ? $custom_list_config['view_mode'] : 'default';
    $preselected_unique_entities = (isset($custom_list_config['unique_entities'])) ? $custom_list_config['unique_entities'] : TRUE;

    // Get all available options.
    $options = [
      'source_list' => $source_lists,
      'view_mode' => $this->getViewModeList($source_list),
    ];

    // Sub-form will be created for custom list form.
    $custom_list_config_form = [];

    $custom_list_config_form['options'] = [
      '#type' => 'hidden',
      '#default_value' => json_encode($options),
    ];

    $custom_list_config_form['source_list'] = [
      '#type' => 'select',
      '#title' => $this->t('Source list'),
      '#options' => $options['source_list'],
      '#default_value' => $source_list,
      '#ajax' => [
        'callback' => [$this, 'onSourceListChange'],
      ],
    ];

    $custom_list_config_form['add_source_list'] = [
      '#type' => 'link',
      '#title' => $this->t('Add source list'),
      '#url' => Url::fromRoute('entity.source_list.add_form'),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button',
          'custom-list__add-source-list__button',
          'custom-list__add-source-list',
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
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

    $custom_list_config_form['unique_entities'] = $this->getUniqueSelector($preselected_unique_entities);
    $custom_list_config_form['insertions'] = $this->getInsertionsForm((!empty($custom_list_config['insertions'])) ? $custom_list_config['insertions'] : []);

    $form['custom_list_config_form'] = $custom_list_config_form;

    return $form;
  }

  /**
   * Handler for Ajax request when source list selection is changed.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response object.
   */
  public function onSourceListChange(array $form, FormStateInterface $form_state) {
    $result = new AjaxResponse();

    $result->addCommand(new ReplaceCommand('.form-item-settings-custom-list-config-form-view-mode', $form['settings']['custom_list_config_form']['view_mode']));

    return $result;
  }

  /**
   * Get list of view modes for source list.
   *
   * @param string $source_list_id
   *   The source list ID.
   *
   * @return array|null
   *   Return list of view modes for source list.
   */
  protected function getViewModeList($source_list_id) {
    $view_mode_list = [
      'default' => $this->t('Default'),
    ];

    if (empty($source_list_id)) {
      return $view_mode_list;
    }

    try {
      /** @var \Drupal\custom_list\Entity\SourceListEntity $source_list */
      $source_list = $this->getSourceList($source_list_id);
    }
    catch (InvalidPluginDefinitionException $e) {
      return $view_mode_list;
    }
    catch (PluginNotFoundException $e) {
      return $view_mode_list;
    }

    try {
      /** @var \Drupal\custom_list\Plugin\SourceListPluginInterface $plugin */
      $plugin = $this->sourceListPluginManager->createInstance($source_list->getPluginId(), $source_list->getConfig()->getValue()[0]);
    }
    catch (PluginException $e) {
      return $view_mode_list;
    }

    $view_modes = [];

    // TODO: add support for different view modes for different entity types!
    $entity_type_infos = $plugin->getEntityTypeInfo();
    foreach ($entity_type_infos as $entity_type_info) {
      $view_modes = array_merge($view_modes, $this->displayRepository->getViewModes($entity_type_info['entity_type']));
    }

    foreach ($view_modes as $view_mode_id => $view_mode) {
      if ($view_mode['status']) {
        $view_mode_list[$view_mode_id] = $view_mode['label'];
      }
    }

    return $view_mode_list;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $custom_list_config = $form_state->getValue('custom_list_config_form');
    $custom_list_config['insertions'] = $this->fetchInsertSelection($custom_list_config['insertions']);
    $custom_list_config['unique_entities'] = $this->fetchUniqueSelector($custom_list_config['unique_entities']);
    unset($custom_list_config['options']);

    $config = $this->getConfiguration();
    $config['custom_list'] = $custom_list_config;
    $this->setConfiguration($config);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $custom_list_config = $this->configuration['custom_list'];

    /** @var \Drupal\custom_list\Entity\SourceListEntityInterface $source_list */
    try {
      $source_list = $this->getSourceList($custom_list_config['source_list']);
    }
    catch (InvalidPluginDefinitionException $e) {
      return [];
    }
    catch (PluginNotFoundException $e) {
      return [];
    }

    if (empty($source_list)) {
      return [];
    }

    /** @var \Drupal\custom_list\Plugin\SourceListPluginInterface $plugin */
    try {
      $plugin = $this->sourceListPluginManager->createInstance($source_list->getPluginId(), $source_list->getConfig()->getValue()[0]);
    }
    catch (PluginException $e) {
      // TODO: some info that block is broken!!!
      return [];
    }

    $view_config = $plugin->generateConfiguration('view', $custom_list_config);

    $view = new View($view_config, 'view');
    return $view->getExecutable()->render('custom_list_block');
  }

  /**
   * Provides form for adding insertions.
   *
   * @param array $selection
   *   Existing selection of entities.
   *
   * @return array
   *   Returns form elements.
   */
  protected function getInsertionsForm(array $selection) {
    $insertions_form = [];

    $insertions_form['insertion_selection'] = [
      '#type' => 'hidden',
      '#default_value' => json_encode($selection),
      '#attached' => [
        'library' => [
          'custom_list/insert_selector',
        ],
      ],
      '#attributes' => [
        'class' => ['custom-list__insertion-selection'],
        'data-entity-browser' => 'entity_browser_selector',
      ],
    ];

    // TODO: configurable entity browsers - in some way!
    $insertions_form['entity_browser_selector'] = [
      '#type' => 'entity_browser',
      '#entity_browser' => 'custom_list_articles',
    ];

    $insertions_form['add_block'] = [
      '#type' => 'link',
      '#title' => $this->t('Add insertion block'),
      '#url' => Url::fromRoute('custom_list.add_block_list'),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button',
          'custom-list__add-block__button',
          'custom-list__add-block',
        ],
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

    return $insertions_form;
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
    return json_decode($insert_form_values['insertion_selection'], TRUE);
  }

  /**
   * Get source list entity.
   *
   * @param string $source_list_id
   *   Source list ID.
   *
   * @return \Drupal\custom_list\Entity\SourceListEntityInterface
   *   Return source list entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getSourceList($source_list_id) {
    return $this->entityTypeManager->getStorage('source_list')->load($source_list_id);
  }

  /**
   * Get all available source list entities for selection.
   *
   * TODO: get list only with available plugins.
   *
   * @return array
   *   Return list of source lists for selection.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getSourceLists() {
    /** @var \Drupal\custom_list\Entity\SourceListEntity[] $source_list_entities */
    $source_list_entities = $this->entityTypeManager->getStorage('source_list')
      ->loadMultiple();

    $source_lists = [];
    foreach ($source_list_entities as $entity_id => $entity) {
      $source_lists[$entity_id] = $entity->label();
    }

    return $source_lists;
  }

}
