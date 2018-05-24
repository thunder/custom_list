/**
 * @file custom-list-insert-selector.js
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.loadCustomListInsertSelector = {
    attach: function (context) {
      var $form = $(context)
        .find('*[name="settings[custom_list_config_form][insertion_form][insert_selection]"]')
        .once('load-custom-list-insert');

      if ($form.length > 0) {
        var form = new Drupal.custom_list.InsertionForm();
        $form.after(form.render().el);

        var $entityBrowserElement = $(context).find('*[name="settings[custom_list_config_form][insertion_form][entity_browser_selector][entity_ids]"]');
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
      }
    }
  };

})(jQuery, Drupal);
