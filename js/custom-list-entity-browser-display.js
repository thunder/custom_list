/**
 * @file custom-list-entity-browser-display.js
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Registers behaviours for custom list EB display.
   */
  Drupal.behaviors.customListEntityBrowserDisplay = {
    attach: function (context) {
      var $exposedForm = jQuery(context).find('.views-exposed-form');

      // Add key press event handler.
      $exposedForm.find('.form-item').once('custom-list-filter-add-on-enter-key').find('input').each(function () {
        $(this).on('keypress', function (event) {
          if (event.keyCode === 13) {
            event.preventDefault();

            var $this = jQuery(this);

            // When value is changed we are setting that in query property and
            // additionally paging is reset so that filtered elements are
            // visible after request.
            drupalSettings.path.currentQuery[$this.prop('name')] = $this.val();
            drupalSettings.path.currentQuery.page = '0';

            // Change URL search for IFrame to get filtered view.
            location.search = jQuery.param(drupalSettings.path.currentQuery);
          }
        });
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
