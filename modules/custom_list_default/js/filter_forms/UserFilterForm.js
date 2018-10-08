/**
 * @file UserFilterForm.js
 */

(function ($, _, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list_default.filter_forms.User = Backbone.View.extend({
    template: _.template(
      '<div class="custom-list-default__filter-form__user">' +
      '  <span><%- tplGetValue(value) %></span>' +
      '  <input class="custom-list-default__filter-form__user__edit-value" style="display: none;" type="text" value="<%- tplGetValue(value) %>" />' +
      '</div>'
    ),

    templateHelpers: {
      tplGetValue: function (value) {
        if (_.isEmpty(value)) {
          return value;
        }

        return JSON.parse(value).join(',');
      }
    },

    events: {
      'click span': 'editValue',
      'keypress .custom-list-default__filter-form__user__edit-value': 'updateValueOnEnter',
      'blur .custom-list-default__filter-form__user__edit-value': 'closeEditValue'
    },

    render: function () {
      var render = this.template(_.extend(this.model.toJSON(), this.templateHelpers));
      this.setElement(render);

      this.inputValue = this.$('.custom-list-default__filter-form__user__edit-value');

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

        var userValue = [];
        $.each(valueParts, function(index, userId) {
          userValue.push(userId.trim());
        });

        this.model.set('value', JSON.stringify(userValue));
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
