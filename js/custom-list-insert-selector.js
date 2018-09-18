/**
 * @file custom-list-insert-selector.js
 */

(function ($, Drupal) {

  'use strict';

  /**
   * The custom list insertion form.
   *
   * @type {object}
   */
  Drupal.custom_list.insertion_form = null;

  /**
   * Attaching events for adding of custom list insertion elements.
   *
   * @type Object
   */
  Drupal.behaviors.loadCustomListInsertSelector = {
    attach: function (context) {
      // Some times we have issue with detached context.
      if (document !== context && !$.contains(document, context)) {
        return;
      }

      // Initialize form and persist it in global variable, because it should be
      // available across multiple ajax requests and when entity browser element
      // is re-created.
      var $form = $(context).find('.custom-list__insertion-selection').once('load-custom-list-insert');
      if ($form.length > 0) {
        var form = new Drupal.custom_list.InsertionForm();
        $form.after(form.render().el);

        Drupal.custom_list.insertion_form = form;
      }

      // The entity browser can be re-created and handler has to be able to
      // register it self again in dependently of form or add block button.
      var $entityBrowserElement = $(context).find('.custom-list__insertion-selection').siblings('*[name$="[entity_browser_selector][entity_ids]"]').once('load-custom-list-insert-entity-browser');
      if ($entityBrowserElement.length > 0) {
        $entityBrowserElement.on('entity_browser_value_updated', function () {
          var selection = $entityBrowserElement.val();

          var list = selection.split(' ');
          $.each(list, function (index, value) {
            var form = Drupal.custom_list.insertion_form;

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

      // Register handler for adding a block to insertion list.
      var $addButton = $(context).find('.custom-list__add-block').once('load-custom-list-insert-add-block');
      if ($addButton.length > 0) {
        $addButton.on('custom_list_add_block', function (event, data) {
          var form = Drupal.custom_list.insertion_form;

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
