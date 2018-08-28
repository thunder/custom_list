/**
 * @file custom-list-insert-selector.js
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.loadCustomListInsertSelector = {
    attach: function (context) {
      // Some times we have issue with detached context.
      if (document !== context && !$.contains(document, context)) {
        return;
      }

      var $form = $(context)
        .find('.custom-list__insertion-selection');

      // TODO: find cleaner way to re-register handler on source list change.
      var $entityBrowserElement = $form.siblings('*[name$="[entity_browser_selector][entity_ids]"]').once('load-custom-list-insert');

      if ($entityBrowserElement.length > 0) {
        var form;

        if ($form.data('from-instance')) {
          form = $form.data('from-instance');
        }
        else {
          form = new Drupal.custom_list.InsertionForm();
          $form.after(form.render().el);

          $form.data('from-instance', form);
        }

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

        var $addButton = $('.custom-list__add-block').once('load-custom-list-insert');
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
