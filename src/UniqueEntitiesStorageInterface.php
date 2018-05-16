<?php

namespace Drupal\custom_list;

/**
 * Interface UniqueEntitiesStorageInterface.
 *
 * TODO: Should be renamed since name is misleading.
 */
interface UniqueEntitiesStorageInterface {

  /**
   * Set list of unique IDs to storage.
   *
   * @param string $base_table
   *   Base table used by view.
   * @param string $base_field
   *   Base field used by view.
   * @param array $ids
   *   List of IDs that should be stored.
   */
  public function setIds($base_table, $base_field, array $ids);

  /**
   * Get list of unique IDs already stored.
   *
   * @param string $base_table
   *   Base table used by view.
   * @param string $base_field
   *   Base field used by view.
   *
   * @return array
   *   Returns list of IDs.
   */
  public function getIds($base_table = '', $base_field = '');

}
