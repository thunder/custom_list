<?php

namespace Drupal\custom_list\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The custom list settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * List of entity browsers.
   *
   * @var array
   */
  protected $entityBrowsers = NULL;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'custom_list.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * Get list of entity browsers available for selection.
   *
   * @return array
   *   List of entity browsers.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityBrowsers() {
    if ($this->entityBrowsers === NULL) {
      $this->entityBrowsers = [];

      /** @var \Drupal\entity_browser\EntityBrowserInterface $browser */
      foreach ($this->entityTypeManager->getStorage('entity_browser')->loadMultiple() as $browser) {
        $this->entityBrowsers[$browser->id()] = $browser->label();
      }
    }

    return $this->entityBrowsers;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('custom_list.settings');
    $form['entity_browser'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity browser'),
      '#description' => $this->t('The entity browser used for selection of entities for custom list insertion elements'),
      '#options' => $this->getEntityBrowsers(),
      '#default_value' => $config->get('entity_browser'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('custom_list.settings')
      ->set('entity_browser', $form_state->getValue('entity_browser'))
      ->save();
  }

}
