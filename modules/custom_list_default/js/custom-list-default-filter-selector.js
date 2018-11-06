/**
 * @file custom-list-default-filter-selector.js
 */

(function ($, Drupal) {

  'use strict';

  /**
   * The filter list form instance.
   *
   * @type {object}
   */
  Drupal.custom_list_default.filter_list_form = null;

  /**
   * Initialize the filter list form.
   *
   * @type {object}
   */

  Drupal.behaviors.loadCustomListFilterSelector = {
    attach: function (context) {
      // Some times we have issue with detached context.
      if (document !== context && !$.contains(document, context)) {
        return;
      }

      var $form = $(context)
        .find('.custom-list-default__default-source-list-plugin__filter_selection')
        .once('load__custom-list-default__filter');

      if ($form.length > 0) {
        var form = new Drupal.custom_list_default.FilterForm();
        $form.after(form.render().el);

        Drupal.custom_list_default.filter_list_form = form;
      }
      else if ($(context).hasClass('custom-list-default__default-source-list-plugin__options')) {
        Drupal.custom_list_default.filter_list_form.clearForm();
      }
    }
  };

})(jQuery, Drupal);
