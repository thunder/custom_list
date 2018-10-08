/**
 * This abstract class that should be extended.
 *
 * @file SingleValueFilterForm.js
 */

(function ($, _, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list_default.filter_forms.SingleValueFilterForm = Backbone.View.extend({
    template: _.template(Drupal.t('The single value filter form should be extended.')),
    getInputElement: function() {},

    templateHelpers: {
      tplGetValue: function (value) {
        return value && JSON.parse(value);
      }
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

    getValue: function(value) {
      return value;
    },

    closeEditValue: function () {
      var value = this.inputValue.val();

      if (!value) {
        this.model.set('value', '');
      }
      else {
        this.model.set('value', JSON.stringify(this.getValue(value)));
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
