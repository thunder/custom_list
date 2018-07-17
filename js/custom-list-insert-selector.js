/**
 * @file custom-list-insert-selector.js
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.loadCustomListInsertSelector = {
    attach: function (context) {
      // Some times we have issue with detached context.
      if (!$.contains(document, context)) {
        return;
      }

      var $form = $(context)
        .find('.custom-list__insertion-selection')
        .once('load-custom-list-insert');

      if ($form.length > 0) {
        var form = new Drupal.custom_list.InsertionForm();
        $form.after(form.render().el);

        var $entityBrowserElement = $form.siblings('*[name$="[entity_browser_selector][entity_ids]"]');
        $entityBrowserElement.on('entity_browser_value_updated', function () {
          var selection = $entityBrowserElement.val();

          var list = selection.split(' ');
          $.each(list, function (index, value) {
            var entityInfo = value.split(':');

            var entityId = entityInfo[1];
            var entityType = entityInfo[0];

            form.collection.create({
              position: form.collection.length,
              config: {
                id: entityId,
                type: entityType,
                name: entityType.substr(0, 1)
                  .toUpperCase() + entityType.substr(1) + ' (' + entityId + ')',
                view_mode: 'default'
              }
            });
          });
        });

        var $addButton = $('.custom-list__add-block');
        $addButton.on('custom_list_add_block', function (event, data) {
          form.collection.create({
            position: form.collection.length,
            type: 'block',
            config: {
              type: data.id,
              config: data
            }
          });
        });
      }
    }
  };

})(jQuery, Drupal);
