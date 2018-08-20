<?php

namespace Drupal\custom_list\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_list\Ajax\AddSourceListCommand;
use Drupal\custom_list\Plugin\SourceListPluginManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Source List edit forms.
 *
 * @ingroup custom_list
 */
class SourceListEntityForm extends ContentEntityForm {

  /**
   * The source list plugin manager.
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
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\custom_list\Plugin\SourceListPluginManager $source_list_plugin_manager
   *   The source list plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger for custom list module.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, SourceListPluginManager $source_list_plugin_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, LoggerInterface $logger) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->sourceListPluginManager = $source_list_plugin_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('plugin.manager.source_list_plugin'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('custom_list.logger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!empty($form_state->getUserInput()['_drupal_ajax'])) {
      $form_state->set('_ajax_response', TRUE);
    }

    /* @var $entity \Drupal\custom_list\Entity\SourceListEntity */
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\custom_list\Entity\SourceListEntityInterface $entity */
    $entity = $this->entity;

    $plugins = $this->sourceListPluginManager->getDefinitions();

    $plugin_options = [];
    /** @var \Drupal\custom_list\Annotation\SourceListPlugin $plugin */
    foreach ($plugins as $plugin_id => $plugin) {
      $plugin_options[$plugin_id] = $plugin['label'];
    }

    $preselected_plugin_id = (!empty($entity->getPluginId())) ? $entity->getPluginId() : key($plugin_options);

    $form['source_list_plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Source list'),
      '#options' => $plugin_options,
      '#default_value' => $preselected_plugin_id,
      '#ajax' => [
        'callback' => [$this, 'onPluginChange'],
      ],
    ];

    if ($form_state->getValue('source_list_plugin')) {
      $preselected_plugin_id = $form_state->getValue('source_list_plugin');
    }

    /** @var \Drupal\custom_list\Plugin\SourceListPluginInterface $plugin_instance */
    try {
      $plugin_instance = $this->sourceListPluginManager->createInstance($preselected_plugin_id, $entity->getConfig());

      $form['plugin_subform'] = $plugin_instance->getForm();
      $form['plugin_subform']['#type'] = 'container';
      $form['plugin_subform']['#attributes']['class'][] = 'custom-list__plugin-subform';
    }
    catch (PluginException $e) {
      $form['plugin_subform'] = [
        '#markup' => $this->t('Unable to render plugin form because selected plugin is not available.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    if ($form_state->get('_ajax_response')) {
      $actions['submit']['#ajax'] = [
        'callback' => '::ajaxSubmitForm',
      ];
    }

    return $actions;
  }

  /**
   * Handles ajax response when form is called as modal dialog.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Returns ajax response.
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $response->addCommand(new CloseModalDialogCommand());

    $response->addCommand(new AddSourceListCommand($this->entity->id(), $this->entity->label()));
    $response->setAttachments([
      'library' => ['custom_list/add_source_list'],
    ]);

    return $response;
  }

  /**
   * Handles switching of the content type.
   */
  public function onPluginChange($form, FormStateInterface $form_state) {
    $result = new AjaxResponse();

    $result->addCommand(new ReplaceCommand('.custom-list__plugin-subform', $form['plugin_subform']));

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\custom_list\Entity\SourceListEntity $entity */
    $entity = $this->entity;

    if ($form_state->getValue('source_list_plugin')) {
      $preselected_plugin_id = $form_state->getValue('source_list_plugin');
      $entity->setPluginId($preselected_plugin_id);

      /** @var \Drupal\custom_list\Plugin\SourceListPluginInterface $plugin_instance */
      $plugin_instance = $this->sourceListPluginManager->createInstance($preselected_plugin_id, []);

      $plugin_config = $plugin_instance->getFormData($form, $form_state);
      $entity->setConfig($plugin_config);
    }
    else {
      $this->logger->error(sprintf('Plugin is not defined for source list (ID: %s) and it is saved as empty list.', $entity->id()));

      $entity->setPluginId('');
      $entity->setConfig([]);
    }

    $status = parent::save($form, $form_state);
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage(
          $this->t('Created the %label Source List.', [
            '%label' => $entity->label(),
          ])
        );
        break;

      default:
        $this->messenger()->addMessage(
          $this->t('Saved the %label Source List.', [
            '%label' => $entity->label(),
          ])
        );
    }

    return $status;
  }

}
