/**
 * @file StringFilterForm.js
 */

(function ($, _, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list_default.filter_forms.String = Drupal.custom_list_default.filter_forms.SingleValueFilterForm.extend({
    template: _.template(
      '<div class="custom-list-default__filter-form__string">' +
      '  <span><%- tplGetValue(value) %></span>' +
      '  <input class="custom-list-default__filter-form__string__edit-value" style="display: none;" type="text" value="<%- tplGetValue(value) %>" />' +
      '</div>'
    ),
    getInputElement: function () {
      return this.$('.custom-list-default__filter-form__string__edit-value');
    },

    events: {
      'click span': 'editValue',
      'keypress .custom-list-default__filter-form__string__edit-value': 'updateValueOnEnter',
      'blur .custom-list-default__filter-form__string__edit-value': 'closeEditValue'
    }
  });

})(jQuery, _, Drupal, Backbone);
