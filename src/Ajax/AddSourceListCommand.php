<?php

namespace Drupal\custom_list\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class for add source list command.
 */
class AddSourceListCommand implements CommandInterface {

  protected $sourceListId;

  protected $sourceListName;

  /**
   * Add source list command constructor.
   *
   * @param int $source_list_id
   *   The source list ID that will be passed to JavaScript to add option.
   * @param string $source_list_name
   *   The source list name that will be passed to JavaScript to add option.
   */
  public function __construct($source_list_id, $source_list_name) {
    $this->sourceListId = $source_list_id;
    $this->sourceListName = $source_list_name;
  }

  /**
   * Render custom ajax command.
   *
   * @return array
   *   Command function.
   */
  public function render() {
    return [
      'command' => 'addSourceList',
      'source_list_id' => $this->sourceListId,
      'source_list_name' => $this->sourceListName,
    ];
  }

}
