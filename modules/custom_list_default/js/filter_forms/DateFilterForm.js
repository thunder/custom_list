/**
 * @file DateFilterForm.js
 */

(function ($, _, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list_default.filter_forms.Date = Backbone.View.extend({
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
          inputElements += '<input class="custom-list-default__filter-form__date__edit-regex" type="text" value="' + parsedValue.value + '" />';
        }
        else if (operator === 'between' || operator === 'not between') {
          var minDate = parsedValue.min.split(' ');
          inputElements += '<input class="custom-list-default__filter-form__date__edit-min-date" type="date" value="' + (minDate[0] || '') + '" />';
          inputElements += '<input class="custom-list-default__filter-form__date__edit-min-time" type="time" value="' + (minDate[1] || '') + '" />';

          var maxDate = parsedValue.max.split(' ');
          inputElements += '<input class="custom-list-default__filter-form__date__edit-max-date" type="date" value="' + (maxDate[0] || '') + '" />';
          inputElements += '<input class="custom-list-default__filter-form__date__edit-max-time" type="time" value="' + (maxDate[1] || '') + '" />';
        }
        else {
          var singleDate = parsedValue.value.split(' ');
          inputElements += '<input class="custom-list-default__filter-form__date__edit-single-date" type="date" value="' + (singleDate[0] || '') + '" />';
          inputElements += '<input class="custom-list-default__filter-form__date__edit-single-time" type="time" value="' + (singleDate[1] || '') + '" />';
        }

        return inputElements;
      }
    },

    events: {
      'blur .custom-list-default__filter-form__date__edit-regex': 'updateValue',
      'blur .custom-list-default__filter-form__date__edit-min-date': 'updateValue',
      'blur .custom-list-default__filter-form__date__edit-min-time': 'updateValue',
      'blur .custom-list-default__filter-form__date__edit-max-date': 'updateValue',
      'blur .custom-list-default__filter-form__date__edit-max-time': 'updateValue',
      'blur .custom-list-default__filter-form__date__edit-single-date': 'updateValue',
      'blur .custom-list-default__filter-form__date__edit-single-time': 'updateValue'
    },

    render: function () {
      var render = this.template(_.extend(this.model.toJSON(), this.templateHelpers));
      this.setElement(render);

      return this;
    },

    updateValue: function () {
      var operator = this.model.get('operator');
      var dateValue = {
        min: '',
        max: '',
        value: '',
        type: 'date'
      };

      if (operator === 'regular_expression') {
        dateValue.value = this.$('.custom-list-default__filter-form__date__edit-regex').val();
      }
      else if (operator === 'between' || operator === 'not between') {
        dateValue.min = this.$('.custom-list-default__filter-form__date__edit-min-date').val() + ' ' + this.$('.custom-list-default__filter-form__date__edit-min-time').val();
        dateValue.max = this.$('.custom-list-default__filter-form__date__edit-max-date').val() + ' ' + this.$('.custom-list-default__filter-form__date__edit-max-time').val();
      }
      else {
        dateValue.value = this.$('.custom-list-default__filter-form__date__edit-single-date').val() + ' ' + this.$('.custom-list-default__filter-form__date__edit-single-time').val();
      }

      this.model.set('value', JSON.stringify(dateValue));
    }

  });

})(jQuery, _, Drupal, Backbone);
