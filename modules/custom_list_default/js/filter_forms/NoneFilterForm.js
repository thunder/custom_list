/**
 * @file NoneFilterForm.js
 */

(function ($, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list_default.filter_forms.None = Backbone.View.extend({
    template: _.template(
      '<div class="custom-list-default__filter-form__none">' +
      '  <span><b>' + Drupal.t('not available') + '</b></span>' +
      '</div>'
    ),

    render: function () {
      this.setElement(this.template());

      return this;
    }
  });

})(jQuery, Drupal, Backbone);
