<?php

namespace Drupal\custom_list\Plugin\views\display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\Plugin\views\display\EntityBrowser;

/**
 * The plugin that handles entity browser display for custom list.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "source_list_display",
 *   title = @Translation("Custom list source list"),
 *   help = @Translation("Displays a view as Entity browser widget."),
 *   theme = "views_view",
 *   admin = @Translation("Custom list source list"),
 *   entity_browser_display = TRUE
 * )
 */
class SourceListDisplay extends EntityBrowser {

  /**
   * {@inheritdoc}
   */
  public function ajaxEnabled() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($option) {
    if ($option == 'use_ajax') {
      return FALSE;
    }
    else {
      return parent::getOption($option);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['use_ajax']['default'] = FALSE;

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);
    if (isset($options['use_ajax'])) {
      $options['use_ajax']['value'] = $this->t('No (Forced)');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    if (isset($form['use_ajax'])) {
      $form['use_ajax'] = [
        '#description' => $this->t('Custom list entity browser view display should not use AJAX.'),
        '#type' => 'checkbox',
        '#title' => $this->t('Use AJAX'),
        '#default_value' => 0,
        '#disabled' => TRUE,
      ];
    }
  }

}
