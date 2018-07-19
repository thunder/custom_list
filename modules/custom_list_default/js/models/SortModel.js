/**
 * @file SortModel.js
 */

(function ($, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list_default.SortModel = Backbone.Model.extend({

    defaults: function () {
      return {
        sort_id: 'node_field_data.changed',
        order: 'DESC'
      };
    },

    sync: function () {}
  });

})(jQuery, Drupal, Backbone);
