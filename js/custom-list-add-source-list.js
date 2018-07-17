/**
 * @file custom-list-add-source-list.js
 */

(function ($, Drupal) {
  'use strict';

  Drupal.AjaxCommands.prototype.addSourceList = function (ajax, response, status) {
    var $sourceList = $('[name$="[custom_list_config_form][source_list]"]');

    $sourceList.append(
      $('<option/>', {
        value: response.source_list_id,
        text: response.source_list_name
      })
    );

    $sourceList.val(response.source_list_id);
  };

})(jQuery, Drupal);
