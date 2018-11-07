/**
 * @file custom-list-default-sort-selector.js
 */

(function ($, Drupal) {

  'use strict';

  /**
   * The sorting list form instance.
   *
   * @type {object}
   */
  Drupal.custom_list_default.sort_list_form = null;

  /**
   * Initialize the sorting list form.
   *
   * @type {object}
   */
  Drupal.behaviors.loadDefaultCustomListSortSelector = {
    attach: function (context) {
      // Some times we have issue with detached context.
      if (document !== context && !$.contains(document, context)) {
        return;
      }

      var $form = $(context)
        .find('.custom-list-default__default-source-list-plugin__sort_selection')
        .once('load__custom-list-default__sort');

      if ($form.length > 0) {
        var form = new Drupal.custom_list_default.SortForm();
        $form.after(form.render().el);

        Drupal.custom_list_default.sort_list_form = form;
      }
      else if ($(context).hasClass('custom-list-default__default-source-list-plugin__options')) {
        Drupal.custom_list_default.sort_list_form.clearForm();
      }
    }
  };

})(jQuery, Drupal);
