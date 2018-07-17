<?php

namespace Drupal\custom_list\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Source list plugin plugins.
 */
abstract class SourceListPluginBase extends PluginBase implements SourceListPluginInterface {

  use StringTranslationTrait;

}
