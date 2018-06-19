<?php

namespace Drupal\custom_list\Ajax;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\CommandInterface;

/**
 * Ajax add block command.
 */
class AddBlockCommand implements CommandInterface {

  /**
   * Block configuration array.
   *
   * @var array
   */
  protected $blockConfig;

  /**
   * Constructor for Ajax command.
   *
   * @param array $block_config
   *   Block configuration array.
   */
  public function __construct(array $block_config) {
    $this->blockConfig = $block_config;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'custom_list_add_block',
      'block_config' => Json::encode($this->blockConfig),
    ];
  }

}
