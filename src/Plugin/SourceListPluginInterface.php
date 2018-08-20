<?php

namespace Drupal\custom_list\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Source list plugin plugins.
 */
interface SourceListPluginInterface extends PluginInspectionInterface {

  /**
   * Creates sub-form render array.
   *
   * @return array
   *   Returns sub-form array.
   */
  public function getForm();

  /**
   * Get configuration for source plugin sub-form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array
   *   Get array with configuration for source list plugin.
   */
  public function getFormData(array $form, FormStateInterface $form_state);

  /**
   * Get entity type info for entities provided by source list.
   *
   * @return array
   *   List of entity type info.
   */
  public function getEntityTypeInfo();

  /**
   * Generate configuration for different consumers.
   *
   * Currently only 'view' is provided but in future we could introduce new
   * source list consumer types.
   *
   * @param string $consumer_type
   *   Type of source list consumer.
   * @param array $custom_list_config
   *   Configuration for custom list.
   *
   * @return array
   *   Generated configuration.
   */
  public function generateConfiguration($consumer_type, array $custom_list_config);

}
