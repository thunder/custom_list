/**
 * @file FilterList.js
 */

(function ($, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list_default.FilterList = Backbone.View.extend({
    tagName: 'li',
    template: _.template(
      '<div class="custom-list-default__filter-row">' +
      '  <div class="custom-list-default__filter-row__filter_id"><%= tplFilterSelector(filter_id) %></div>' +
      '  <div class="custom-list-default__filter-row__operator"><%= tplOperatorSelector(filter_id, operator) %></div>' +
      '  <div class="custom-list-default__filter-row__value"><%- value %></div>' +
      '  <div><a class="custom-list-default__filter-row__remove">' + Drupal.t('Remove') + '</a></div>' +
      '</div>' +
      '<input class="custom-list-default__filter-row__edit-value" style="display: none;" type="text" value="<%- value %>" />'
    ),

    templateHelpers: {
      tplFilterSelector: function (selectedFilterId) {
        // Create HTML for filter field selection.
        var select = '<select class="custom-list-default__filter-row__filter_id_selection">';
        $.each(this.filterOptions.filter, function (filterId, filter) {
          select += '<option value="' + filterId + '" ' + ((filterId === selectedFilterId) ? 'selected' : '') + '>' + filter.field + '</option>';
        });
        select += '</select>';

        return select;
      },
      tplOperatorSelector: function (selectedFilterId, selectedOperator) {
        // Create HTML for filter operator selection.
        var select = '<select class="custom-list-default__filter-row__operator_selection">';
        $.each(this.filterOptions.filter[selectedFilterId].operators, function (operatorId, operatorName) {
          select += '<option value="' + operatorId + '" ' + ((operatorId === selectedOperator) ? 'selected' : '') + '>' + operatorName + '</option>';
        });
        select += '</select>';

        return select;
      }
    },

    events: {
      'click a.custom-list-default__filter-row__remove': 'removeModel',
      'change .custom-list-default__filter-row__filter_id_selection': 'changeField',
      'change .custom-list-default__filter-row__operator_selection': 'changeOperator',
      'click .custom-list-default__filter-row__value': 'editValue',
      'keypress .custom-list-default__filter-row__edit-value': 'updateValueOnEnter',
      'blur .custom-list-default__filter-row__edit-value': 'closeEditValue'
    },

    initialize: function () {
      this.listenTo(this.model, 'change', this.render);
      this.listenTo(this.model, 'destroy', this.remove);

      this.templateHelpers.filterOptions = JSON.parse($('.custom-list-default__default-source-list-plugin__options').val());

      this.persitElement = $('.custom-list-default__default-source-list-plugin__filter_selection');
    },

    persistCollection: function () {
      this.persitElement.val(JSON.stringify(this.model.collection.toJSON()));
    },

    render: function () {
      var data = this.model.toJSON();
      var render = this.template(_.extend(data, this.templateHelpers));

      this.persistCollection();

      this.$el.html(render);

      this.inputValue = this.$('.custom-list-default__filter-row__edit-value');

      return this;
    },

    changeField: function (event) {
      var me = this;
      var value = $(event.target).val();

      me.model.set('filter_id', value);

      // Get first operator for selected field ID.
      $.each(me.templateHelpers.filterOptions.filter[value].operators, function (operator_id) {
        me.model.set('operator', operator_id);

        return false;
      });

      me.model.trigger('change', me.model);
    },

    changeOperator: function (event) {
      var value = $(event.target).val();

      this.model.set('operator', value);
      this.model.trigger('change', this.model);
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
        this.clear();
      }
      else {
        this.model.set('value', value);
      }

      this.inputValue.hide();
    },

    updateValueOnEnter: function (e) {
      if (e.keyCode === 13) {
        e.stopPropagation();
        e.preventDefault();

        this.closeEditValue();
      }
    },

    removeModel: function () {
      // We need collection from model before we get values after destroying model.
      var collection = this.model.collection;

      this.model.destroy();

      this.persitElement.val(JSON.stringify(collection.toJSON()));
    }
  });

})(jQuery, Drupal, Backbone);
