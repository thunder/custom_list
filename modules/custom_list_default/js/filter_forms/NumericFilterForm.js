/**
 * @file NumericFilterForm.js
 */

(function ($, _, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list_default.filter_forms.Numeric = Backbone.View.extend({
    template: _.template(
      '<div class="custom-list-default__filter-form__date">' +
      '  <%= tplGetFields(operator, value) %>' +
      '</div>'
    ),

    templateHelpers: {
      tplGetFields: function (operator, value) {
        var inputElements = '';
        var parsedValue = {};

        if (_.isEmpty(value)) {
          parsedValue = {
            value: '',
            min: '',
            max: ''
          };
        }
        else {
          parsedValue = JSON.parse(value);
        }

        if (operator === 'regular_expression') {
          inputElements += '<input class="custom-list-default__filter-form__numeric__edit-regex" type="text" value="' + parsedValue.value + '" />';
        }
        else if (operator === 'between' || operator === 'not between') {
          inputElements += '<input class="custom-list-default__filter-form__numeric__edit-min" type="number" value="' + parsedValue.min + '" />';
          inputElements += '<input class="custom-list-default__filter-form__numeric__edit-max" type="number" value="' + parsedValue.max + '" />';
        }
        else {
          inputElements += '<input class="custom-list-default__filter-form__numeric__edit-value" type="number" value="' + parsedValue.value + '" />';
        }

        return inputElements;
      }
    },

    events: {
      'blur .custom-list-default__filter-form__numeric__edit-regex': 'updateValue',
      'blur .custom-list-default__filter-form__numeric__edit-min': 'updateValue',
      'blur .custom-list-default__filter-form__numeric__edit-max': 'updateValue',
      'blur .custom-list-default__filter-form__numeric__edit-value': 'updateValue'
    },

    render: function () {
      var render = this.template(_.extend(this.model.toJSON(), this.templateHelpers));
      this.setElement(render);

      return this;
    },

    updateValue: function () {
      var operator = this.model.get('operator');
      var numericValue = {
        min: '',
        max: '',
        value: ''
      };

      if (operator === 'regular_expression') {
        numericValue.value = this.$('.custom-list-default__filter-form__numeric__edit-regex').val();
      }
      else if (operator === 'between' || operator === 'not between') {
        numericValue.min = this.$('.custom-list-default__filter-form__numeric__edit-min').val();
        numericValue.max = this.$('.custom-list-default__filter-form__numeric__edit-max').val();
      }
      else {
        numericValue.value = this.$('.custom-list-default__filter-form__numeric__edit-value').val();
      }

      this.model.set('value', JSON.stringify(numericValue));
    }

  });

})(jQuery, _, Drupal, Backbone);
