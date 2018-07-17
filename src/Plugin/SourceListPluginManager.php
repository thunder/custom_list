<?php

namespace Drupal\custom_list\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Source list plugin plugin manager.
 */
class SourceListPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new SourceListPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/SourceListPlugin', $namespaces, $module_handler, 'Drupal\custom_list\Plugin\SourceListPluginInterface', 'Drupal\custom_list\Annotation\SourceListPlugin');

    $this->alterInfo('custom_list_source_list_plugin_info');
    $this->setCacheBackend($cache_backend, 'custom_list_source_list_plugin_plugins');
  }

}
