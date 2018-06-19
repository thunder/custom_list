/**
 * @file custom-list-sort-selector.js
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.loadCustomListSortSelector = {
    attach: function (context) {
      var $form = $(context)
        .find('*[name="settings[custom_list_config_form][sort_selection]"]')
        .once('load-custom-list-sort');

      if ($form.length > 0) {
        var form = new Drupal.custom_list.SortForm();
        $form.after(form.render().el);
      }
    }
  };

})(jQuery, Drupal);
