<?php

namespace Drupal\custom_list\Plugin\views\display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\Plugin\views\display\EntityBrowser;

/**
 * The plugin that handles entity browser display.
 *
 * "entity_browser_display" is a custom property, used with
 * \Drupal\views\Views::getApplicableViews() to retrieve all views with a
 * 'Entity Browser' display.
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
    // Force AJAX as this Display Plugin will almost always be embedded inside
    // EntityBrowserForm, which breaks normal exposed form submits.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($option) {
    // @todo remove upon resolution of https://www.drupal.org/node/2904798
    // This overrides getOption() instead of ajaxEnabled() because
    // \Drupal\views\Controller\ViewAjaxController::ajaxView() currently calls
    // that directly.
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
    // Disable the ability to toggle AJAX support, as we forcibly enable AJAX
    // in our ajaxEnabled() implementation.
    if (isset($form['use_ajax'])) {
      $form['use_ajax'] = [
        '#description' => $this->t('Entity Browser requires Views to use AJAX.'),
        '#type' => 'checkbox',
        '#title' => $this->t('Use AJAX'),
        '#default_value' => 0,
        '#disabled' => TRUE,
      ];
    }
  }

}
