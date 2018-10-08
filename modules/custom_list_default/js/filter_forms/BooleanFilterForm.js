/**
 * @file BooleanFilterForm.js
 */

(function ($, _, Drupal) {

  'use strict';

  Drupal.custom_list_default.filter_forms.Boolean = Drupal.custom_list_default.filter_forms.SingleValueFilterForm.extend({
    template: _.template(
      '<div class="custom-list-default__filter-form__boolean">' +
      '  <span><%- tplGetValue(value) %></span>' +
      '  <input class="custom-list-default__filter-form__boolean__edit-value" style="display: none;" type="text" value="<%- tplGetValue(value) %>" />' +
      '</div>'
    ),

    getInputElement: function() {
      return this.$('.custom-list-default__filter-form__boolean__edit-value');
    },

    events: {
      'click span': 'editValue',
      'keypress .custom-list-default__filter-form__boolean__edit-value': 'updateValueOnEnter',
      'blur .custom-list-default__filter-form__boolean__edit-value': 'closeEditValue'
    },

    getValue: function(value) {
      return parseInt(value, 10);
    }
  });

})(jQuery, _, Drupal);
