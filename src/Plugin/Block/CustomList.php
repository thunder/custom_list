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
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\custom_list\Plugin\SourceListPluginManager;
use Drupal\views\Entity\View;
use Psr\Log\LoggerInterface;
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
   * The logger for custom list.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The list of view modes for source list ID.
   *
   * @var array
   */
  protected $sourceListViewModes = [];

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
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger for custom list module.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SourceListPluginManager $source_list_plugin_manager, EntityDisplayRepositoryInterface $display_repository, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->sourceListPluginManager = $source_list_plugin_manager;
    $this->displayRepository = $display_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
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
      $container->get('entity_type.manager'),
      $container->get('custom_list.logger')
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

    // Get preselection for the form.
    $base_preselection = [
      'source_list' => key($source_lists),
      'view_mode' => 'default',
      'unique_entities' => TRUE,
      'limit' => 5,
      'insertions' => [],
    ];

    $config_preselection = [];
    foreach ($base_preselection as $preselection_key => $base_value) {
      if (isset($custom_list_config[$preselection_key])) {
        $config_preselection[$preselection_key] = $custom_list_config[$preselection_key];
      }
    }

    $form_preselection = [];
    if ($form_state->isProcessingInput()) {
      // The selected source list state should be changed only when change on
      // select field for source list is changed.
      $triggering_element = $form_state->getTriggeringElement();
      if (!empty($triggering_element) && $triggering_element['#name'] === 'settings[custom_list_config_form][source_list]') {
        if ($form_state instanceof SubformStateInterface) {
          $form_state_with_values = $form_state->getCompleteFormState();
        }
        else {
          $form_state_with_values = $form_state;
        }

        $form_state->set(
          'source_list',
          $form_state_with_values->getValue([
            'settings',
            'custom_list_config_form',
            'source_list',
          ])
        );
      }

      $source_list_state = $form_state->get('source_list');
      if (!empty($source_list_state)) {
        $form_preselection['source_list'] = $source_list_state;
      }
    }

    $preselection = array_merge($base_preselection, $config_preselection, $form_preselection);

    // Get all available options.
    $options = [
      'source_list' => $source_lists,
      'view_mode' => $this->getViewModeList($preselection['source_list']),
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
      '#default_value' => $preselection['source_list'],
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
      '#default_value' => $preselection['view_mode'],
    ];

    // Number of elements that will be displayed.
    $custom_list_config_form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#default_value' => $preselection['limit'],
    ];

    $custom_list_config_form['unique_entities'] = $this->getUniqueSelector($preselection['unique_entities']);
    $custom_list_config_form['insertions'] = $this->getInsertionsForm($preselection['insertions']);

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

    // In case of consecutive calls, we can return value from stored list.
    if (!empty($this->sourceListViewModes[$source_list_id])) {
      return $this->sourceListViewModes[$source_list_id];
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
      $plugin = $this->sourceListPluginManager->createInstance($source_list->getPluginId(), $source_list->getConfig());
    }
    catch (PluginException $e) {
      return $view_mode_list;
    }

    // Only one view mode will be selected for custom list block, that's why we
    // have to find view mode enabled for every bundle type that could be
    // provided by source list.
    $view_modes = [];
    $uninitialized_view_modes = TRUE;
    $entity_type_infos = $plugin->getEntityTypeInfo();
    foreach ($entity_type_infos as $entity_type_info) {
      $bundle_view_modes = $this->displayRepository->getViewModeOptionsByBundle($entity_type_info['entity_type'], $entity_type_info['bundle']);

      if ($uninitialized_view_modes) {
        $view_modes = $bundle_view_modes;
        $uninitialized_view_modes = FALSE;
      }
      else {
        $view_modes = array_intersect($view_modes, $bundle_view_modes);
      }
    }

    foreach ($view_modes as $view_mode_id => $view_mode_label) {
      $view_mode_list[$view_mode_id] = (string) $view_mode_label;
    }

    // Store view modes for source list, to avoid multiple consecutive calls.
    $this->sourceListViewModes[$source_list_id] = $view_mode_list;

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
      $this->logger->warning(sprintf('The plugin definition for source list (ID: %s) is not found.', $custom_list_config['source_list']));

      return [];
    }
    catch (PluginNotFoundException $e) {
      $this->logger->warning(sprintf('The plugin for source list (ID: %s) is not found.', $custom_list_config['source_list']));

      return [];
    }

    if (empty($source_list)) {
      $this->logger->warning(sprintf('The source list (ID: %s) is not available.', $custom_list_config['source_list']));

      return [];
    }

    /** @var \Drupal\custom_list\Plugin\SourceListPluginInterface $plugin */
    try {
      $plugin = $this->sourceListPluginManager->createInstance($source_list->getPluginId(), $source_list->getConfig());
    }
    catch (PluginException $e) {
      $this->logger->warning(sprintf('Unable to render the block, because the plugin for source list (ID: %s) is not available.', $source_list->id()));

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
   * @return array
   *   Return list of source lists for selection.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getSourceLists() {
    /** @var \Drupal\custom_list\Entity\SourceListEntity[] $source_list_entities */
    $source_list_entities = $this->entityTypeManager->getStorage('source_list')->loadMultiple();

    $source_lists = [];
    $available_plug_ids = [];
    foreach ($source_list_entities as $entity_id => $entity) {
      $plugin_id = $entity->getPluginId();

      if (!isset($available_plug_ids[$plugin_id])) {
        try {
          $this->sourceListPluginManager->getDefinition($plugin_id);
          $available_plug_ids[$plugin_id] = TRUE;
        }
        catch (PluginNotFoundException $e) {
          $this->logger->warning(sprintf('The plugin (ID: %s) is not available.', $plugin_id));
          $available_plug_ids[$plugin_id] = FALSE;
        }
      }

      if ($available_plug_ids[$plugin_id]) {
        $source_lists[$entity_id] = $entity->label();
      }
    }

    return $source_lists;
  }

}
