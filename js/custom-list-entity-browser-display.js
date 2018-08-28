/**
 * @file custom-list-entity-browser-display.js
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Registers behaviours related to view widget.
   */
  Drupal.behaviors.entityBrowserView = {
    attach: function (context) {
      var $exposedForm = jQuery(context).find('.views-exposed-form');

      // Add key press event.
      $exposedForm.find('.form-item').once('custom-list-filter-add-on-enter-key').find('input').each(function () {
        $(this).on('keypress', function (event) {
          if (event.keyCode === 13) {
            event.preventDefault();

            var $this = jQuery(this);

            drupalSettings.path.currentQuery[$this.prop('name')] = $this.val();
            drupalSettings.path.currentQuery.page = "0";

            location.search=jQuery.param(drupalSettings.path.currentQuery);
          }
        });
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
