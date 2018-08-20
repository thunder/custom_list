<?php

namespace Drupal\custom_list\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Source list plugin item annotation object.
 *
 * @see \Drupal\custom_list\Plugin\SourceListPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class SourceListPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
