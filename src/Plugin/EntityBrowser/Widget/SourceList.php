<?php

namespace Drupal\custom_list\Plugin\EntityBrowser\Widget;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\custom_list\Plugin\SourceListPluginManager;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\views\Entity\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Uses a source list to provide entity listing in a browser's widget.
 *
 * @EntityBrowserWidget(
 *   id = "source_list",
 *   label = @Translation("Source List"),
 *   provider = "views",
 *   description = @Translation("Uses a source list to provide entity listing in a browser's widget."),
 *   auto_select = TRUE
 * )
 */
class SourceList extends WidgetBase {

  /**
   * Source list plugin manager service.
   *
   * @var \Drupal\custom_list\Plugin\SourceListPluginManager
   */
  protected $sourceListPluginManager;

  /**
   * The logger for custom list.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * WidgetBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   *   The Widget Validation Manager service.
   * @param \Drupal\custom_list\Plugin\SourceListPluginManager $source_list_plugin_manager
   *   The source list plugin manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger for custom list module.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, SourceListPluginManager $source_list_plugin_manager, LoggerInterface $logger, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->sourceListPluginManager = $source_list_plugin_manager;
    $this->logger = $logger;
    $this->renderer = $renderer;

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('plugin.manager.source_list_plugin'),
      $container->get('custom_list.logger'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['source_list' => 'NULL'] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    // Attach entity browser view library, because it handles exposed filters.
    $form['#attached']['library'][] = 'custom_list/entity_browser_display';

    // Get selection view for source list.
    $source_list_id = $this->configuration['source_list'];
    $view_config = $this->getSourceListSelectionView($source_list_id);

    // Fallback to the parent form if source list based view is not available.
    if (empty($view_config)) {
      return $form;
    }

    $view = new View($view_config, 'view');

    // Logic for rendering view is copied from entity_browser View widget.
    $form['view'] = $view->getExecutable()->executeDisplay('source_list_display');

    if (!empty($form['view']['entity_browser_select']) && $form_state->isRebuilding()) {
      foreach (Element::children($form['view']['entity_browser_select']) as $child) {
        $form['view']['entity_browser_select'][$child]['#process'][] = [
          '\Drupal\custom_list\Plugin\EntityBrowser\Widget\SourceList',
          'processCheckbox',
        ];
        $form['view']['entity_browser_select'][$child]['#process'][] = [
          '\Drupal\Core\Render\Element\Checkbox',
          'processAjaxForm',
        ];
        $form['view']['entity_browser_select'][$child]['#process'][] = [
          '\Drupal\Core\Render\Element\Checkbox',
          'processGroup',
        ];
      }
    }

    // Hide filter submit form - "Apply" button.
    $form['view']['view']['#view']->exposed_widgets['actions']['#access'] = FALSE;

    $form['view']['view'] = [
      '#markup' => $this->renderer->render($form['view']['view']),
    ];

    return $form;
  }

  /**
   * Get view configuration for entity browser widget.
   *
   * @param string $source_list_id
   *   The source list ID.
   *
   * @return array
   *   Returns view configuration with entity browser display.
   */
  protected function getSourceListSelectionView($source_list_id) {
    /** @var \Drupal\custom_list\Entity\SourceListEntityInterface $source_list */
    try {
      $source_list = $this->entityTypeManager->getStorage('source_list')
        ->load($source_list_id);
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->logger->warning(sprintf('The plugin definition for source list (ID: %s) is not found.', $source_list_id));

      return [];
    }
    catch (PluginNotFoundException $e) {
      $this->logger->warning(sprintf('The plugin for source list (ID: %s) is not found.', $source_list_id));

      return [];
    }

    if (empty($source_list)) {
      $this->logger->warning(sprintf('The source list (ID: %s) is not available.', $source_list_id));

      return [];
    }

    /** @var \Drupal\custom_list\Plugin\SourceListPluginInterface $source_list_plugin */
    try {
      $source_list_plugin = $this->sourceListPluginManager->createInstance($source_list->getPluginId(), $source_list->getConfig());
    }
    catch (PluginException $e) {
      $this->logger->warning(sprintf('Unable to render the block, because the plugin for source list (ID: %s) is not available.', $source_list->id()));

      return [];
    }

    $custom_list_config = [
      'limit' => 20,
      'insertions' => [],
      'unique_entities' => FALSE,
      'view_mode' => 'default',
    ];

    return $source_list_plugin->generateConfiguration('entity_browser_view', $custom_list_config);
  }

  /**
   * Sets the #checked property when rebuilding form.
   *
   * Every time when we rebuild we want all checkboxes to be unchecked.
   *
   * @see \Drupal\Core\Render\Element\Checkbox::processCheckbox()
   */
  public static function processCheckbox(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#checked'] = FALSE;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    if (isset($user_input['entity_browser_select'])) {
      $selected_rows = array_values(array_filter($user_input['entity_browser_select']));
      foreach ($selected_rows as $row) {
        // Verify that the user input is a string and split it.
        // Each $row is in the format entity_type:id.
        if (is_string($row) && $parts = explode(':', $row, 2)) {
          // Make sure we have a type and id present.
          if (count($parts) == 2) {
            try {
              $storage = $this->entityTypeManager->getStorage($parts[0]);
              if (!$storage->load($parts[1])) {
                $message = $this->t('The @type Entity @id does not exist.', [
                  '@type' => $parts[0],
                  '@id' => $parts[1],
                ]);
                $form_state->setError($form['widget']['view']['entity_browser_select'], $message);
              }
            }
            catch (PluginNotFoundException $e) {
              $message = $this->t('The Entity Type @type does not exist.', [
                '@type' => $parts[0],
              ]);
              $form_state->setError($form['widget']['view']['entity_browser_select'], $message);
            }
          }
        }
      }

      // If there weren't any errors set, run the normal validators.
      if (empty($form_state->getErrors())) {
        parent::validate($form, $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    $selected_rows = array_values(array_filter($form_state->getUserInput()['entity_browser_select']));
    $entities = [];
    foreach ($selected_rows as $row) {
      list($type, $id) = explode(':', $row);
      $storage = $this->entityTypeManager->getStorage($type);
      if ($entity = $storage->load($id)) {
        $entities[] = $entity;
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $entities = $this->prepareEntities($form, $form_state);
    $this->selectEntities($entities, $form_state);
  }

}
