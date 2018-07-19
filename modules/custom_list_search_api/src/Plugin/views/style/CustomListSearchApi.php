<?php

namespace Drupal\custom_list_search_api\Plugin\views\style;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\custom_list\UniqueEntitiesStorageInterface;
use Drupal\views\Entity\View;
use Drupal\custom_list\Plugin\views\style\CustomListBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Search API custom list view style.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "custom_list_search_api",
 *   title = @Translation("Search API custom list"),
 *   help = @Translation("Displays rows one after another with inserts of manually picked entities or blocks."),
 *   theme = "views_view_unformatted",
 *   display_types = {"normal"}
 * )
 */
class CustomListSearchApi extends CustomListBase {

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

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
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UniqueEntitiesStorageInterface $unique_storage, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $unique_storage);

    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('custom_list.unique_entities_store'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getFilter(View $storage, array $entities) {
    $table = $storage->get('base_table');
    $field = $storage->get('base_field');
    $languages = $this->languageManager->getLanguages();

    $not_in_values = [];
    $unique_id = '';
    foreach ($entities as $entity) {
      $entity_type = $entity->getEntityTypeId();
      $entity_id = $entity->id();

      foreach ($languages as $language_id => $language) {
        $search_api_id = "entity:{$entity_type}/{$entity_id}:{$language_id}";
        $unique_id .= "_{$entity_type}-{$entity_id}";

        $not_in_values[$search_api_id] = $search_api_id;
      }
    }

    return [
      "id" => "custom_list_search_api_{$table}_{$field}{$unique_id}",
      "table" => $table,
      "field" => $field,
      "relationship" => "none",
      "group_type" => "group",
      "admin_label" => "",
      "operator" => "not in",
      "value" => $not_in_values,
      "group" => "1",
      "exposed" => FALSE,
      "is_grouped" => FALSE,
      "plugin_id" => "custom_list_search_api_id",
    ];
  }

}
