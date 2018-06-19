/**
 * @file InsertionModel.js
 */

(function ($, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list.InsertionModel = Backbone.Model.extend({

    // TODO: It can be block or entity - maybe make different properties.
    defaults: function () {
      return {
        position: 0,
        type: 'entity',
        config: {
          id: 1,
          type: 'node',
          name: 'Node (1)',
          view_mode: 'default'
        }
      };
    },

    sync: function () {}
  });

})(jQuery, Drupal, Backbone);
