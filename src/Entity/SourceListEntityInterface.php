<?php

namespace Drupal\custom_list\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Source List entities.
 *
 * @ingroup custom_list
 */
interface SourceListEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Source List name.
   *
   * @return string
   *   Name of the Source List.
   */
  public function getName();

  /**
   * Sets the Source List name.
   *
   * @param string $name
   *   The Source List name.
   *
   * @return \Drupal\custom_list\Entity\SourceListEntityInterface
   *   The called Source List entity.
   */
  public function setName($name);

  /**
   * Gets the Source List config.
   *
   * @return string
   *   Plugin ID of the Source List.
   */
  public function getPluginId();

  /**
   * Sets the Source List plugin ID.
   *
   * @param string $plugin_id
   *   The Source List plugin ID.
   *
   * @return \Drupal\custom_list\Entity\SourceListEntityInterface
   *   The called Source List entity.
   */
  public function setPluginId($plugin_id);

  /**
   * Gets the Source List config.
   *
   * @return array
   *   Config of the Source List.
   */
  public function getConfig();

  /**
   * Sets the Source List config.
   *
   * @param array $config
   *   The Source List config.
   *
   * @return \Drupal\custom_list\Entity\SourceListEntityInterface
   *   The called Source List entity.
   */
  public function setConfig(array $config);

  /**
   * Gets the Source List creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Source List.
   */
  public function getCreatedTime();

  /**
   * Sets the Source List creation timestamp.
   *
   * @param int $timestamp
   *   The Source List creation timestamp.
   *
   * @return \Drupal\custom_list\Entity\SourceListEntityInterface
   *   The called Source List entity.
   */
  public function setCreatedTime($timestamp);

}
