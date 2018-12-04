/**
 * @file FilterModel.js
 */

(function ($, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list_default.FilterModel = Backbone.Model.extend({

    defaults: function () {
      return {
        filter_id: 'node_field_data.title',
        operator: 'in',
        value: ''
      };
    },

    sync: function () {}
  });

})(jQuery, Drupal, Backbone);
