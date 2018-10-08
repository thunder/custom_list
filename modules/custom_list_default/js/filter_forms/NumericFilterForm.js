/**
 * @file NumericFilterForm.js
 */

(function ($, _, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list_default.filter_forms.Numeric = Backbone.View.extend({
    template: _.template(
      '<div class="custom-list-default__filter-form__numeric">' +
      '  <span><%- tplGetValue(value) %></span>' +
      '  <input class="custom-list-default__filter-form__numeric__edit-value" style="display: none;" type="text" value="<%- tplGetValue(value) %>" />' +
      '</div>'
    ),

    templateHelpers: {
      tplGetValue: function (value) {
        if (_.isEmpty(value)) {
          return value;
        }

        var numericValue = JSON.parse(value);

        if (numericValue.value) {
          return numericValue.value;
        } else {
          return numericValue.min + ',' + numericValue.max;
        }
      }
    },

    events: {
      'click span': 'editValue',
      'keypress .custom-list-default__filter-form__numeric__edit-value': 'updateValueOnEnter',
      'blur .custom-list-default__filter-form__numeric__edit-value': 'closeEditValue'
    },

    render: function () {
      var render = this.template(_.extend(this.model.toJSON(), this.templateHelpers));
      this.setElement(render);

      this.inputValue = this.$('.custom-list-default__filter-form__numeric__edit-value');

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

    closeEditValue: function () {
      var value = this.inputValue.val();

      if (!value) {
        this.model.set('value', '');
      }
      else {
        var valueParts = value.split(',');
        var numericValue = {
          min: '',
          max: '',
          value: ''
        };

        if (valueParts.length === 2) {
          numericValue.min = valueParts[0].trim();
          numericValue.max = valueParts[1].trim();
        } else {
          numericValue.value = valueParts[0].trim()
        }

        this.model.set('value', JSON.stringify(numericValue));
      }

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
