/**
 * @file custom-list-add-block.js
 */

(function ($, Drupal) {
  'use strict';

  Drupal.AjaxCommands.prototype.custom_list_add_block = function (ajax, response, status) {
    // TODO: This selector should be generic for AJAX command.
    var $addButton = $('.custom-list__add-block');
    var data = JSON.parse(response.block_config);

    $addButton.trigger('custom_list_add_block', [data]);
  };

})(jQuery, Drupal);
