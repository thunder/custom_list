/**
 * @file StringFilterForm.js
 */

(function ($, _, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list_default.filter_forms.String = Backbone.View.extend({
    template: _.template(
      '<div class="custom-list-default__filter-form__string">' +
      '  <span><%- tplGetValue(value) %></span>' +
      '  <input class="custom-list-default__filter-form__string__edit-value" style="display: none;" type="text" value="<%- tplGetValue(value) %>" />' +
      '</div>'
    ),

    templateHelpers: {
      tplGetValue: function (value) {
        return value && JSON.parse(value);
      }
    },

    events: {
      'click span': 'editValue',
      'keypress .custom-list-default__filter-form__string__edit-value': 'updateValueOnEnter',
      'blur .custom-list-default__filter-form__string__edit-value': 'closeEditValue'
    },

    getInputElement: function () {
      return this.$('.custom-list-default__filter-form__string__edit-value');
    },

    render: function () {
      var render = this.template(_.extend(this.model.toJSON(), this.templateHelpers));
      this.setElement(render);

      this.inputValue = this.getInputElement();

      return this;
    },

    editValue: function (event) {
      var $target = $(event.target);

      var cssConfig = $target.position();
      cssConfig.top = cssConfig.top - 1;
      cssConfig.width = $target.width();
      cssConfig.height = $target.height() + 2;

      this.inputValue.css(cssConfig).show().focus().select();
    },

    getValue: function () {
      var value = this.inputValue.val();

      if (!value) {
        return '';
      }

      return JSON.stringify(value);
    },

    closeEditValue: function () {
      this.model.set('value', this.getValue());

      this.inputValue.hide();
    },

    updateValueOnEnter: function (e) {
      if (e.keyCode === 13) {
        e.stopPropagation();
        e.preventDefault();

        this.closeEditValue();
      }
    }

  });

})(jQuery, _, Drupal, Backbone);
