<?php

namespace Drupal\custom_list;

use Drupal\Component\Utility\NestedArray;

/**
 * Class UniqueEntitiesStorage.
 */
class UniqueEntitiesStorage implements UniqueEntitiesStorageInterface {

  /**
   * Th ID storage.
   *
   * @var array
   */
  protected static $storage = [];

  /**
   * {@inheritdoc}
   */
  public function setIds($base_table, $base_field, array $ids) {
    // In order to use simple merge of array we have to prepare array structure.
    $add_ids = [
      $base_table => [
        $base_field => $ids,
      ],
    ];

    static::$storage = NestedArray::mergeDeepArray([static::$storage, $add_ids]);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds($base_table = '', $base_field = '') {
    $path = [];

    // For base table and field we have to adjust parents in order to get
    // requested list.
    if (!empty($base_table)) {
      $path[] = $base_table;

      if (!empty($base_field)) {
        $path[] = $base_field;
      }
    }

    $stored_entities = NestedArray::getValue(static::$storage, $path);

    // Get value on nested array can return NULL if path is no available, in
    // that case no unique IDs are stored yet.
    if (empty($stored_entities)) {
      return [];
    }

    // Array should be flatten, so that only IDs are returned.
    $result = [];
    array_walk_recursive($stored_entities, function ($value, $key) use (&$result) {
      $result[] = $value;
    });

    return $result;
  }

}
