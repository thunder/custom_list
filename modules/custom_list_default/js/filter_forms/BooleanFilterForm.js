/**
 * @file BooleanFilterForm.js
 */

(function ($, _, Drupal) {

  'use strict';

  Drupal.custom_list_default.filter_forms.Boolean = Backbone.View.extend({
    template: _.template(
      '<div class="custom-list-default__filter-form__boolean">' +
      '  <span>' + Drupal.t('True') + '</span>' +
      '</div>'
    ),

    initialize: function () {
      var defaultValue = JSON.stringify(1);

      if (this.model.get('value') !== defaultValue) {
        this.model.set('value', defaultValue);
      }
    },

    render: function () {
      var render = this.template(this.model.toJSON());
      this.setElement(render);

      return this;
    }
  });

})(jQuery, _, Drupal);
