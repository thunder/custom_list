/**
 * @file FilterForm.js
 */

(function ($, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list_default.FilterForm = Backbone.View.extend({
    template: _.template(
      '<div class="custom-list-default__filter-form">' +
      '  <div>' + Drupal.t('Filter List') + '</div>' +
      '  <div class="custom-list-default__filter-form__header">' +
      '    <div>' + Drupal.t('Filter by') + '</div>' +
      '    <div>' + Drupal.t('Operator') + '</div>' +
      '    <div>' + Drupal.t('Value') + '</div>' +
      '    <div>' + Drupal.t('Actions') + '</div>' +
      '  </div>' +
      '  <ul class="custom-list-default__filter-list"></ul>' +
      '  <input type="submit" class="button custom-list-default__filter__add-button" value="' + Drupal.t('Add filter option') + '" />' +
      '</div>'
    ),

    events: {
      'click .custom-list-default__filter__add-button': 'addFilter'
    },

    initialize: function () {
      this.collection = new Backbone.Collection(null, {
        model: Drupal.custom_list_default.FilterModel
      });

      this.listenTo(this.collection, 'add', this.addOne);
      this.listenTo(this.collection, 'reset', this.addAll);
      this.listenTo(this.collection, 'all', this.render);

      this.list = this.$('.custom-list-default__filter-list');

      this.setElement(this.template());

      // Load existing data.
      var dataElement = $('.custom-list-default__default-source-list-plugin__filter_selection');
      this.collection.set(JSON.parse(dataElement.val()));
    },

    getFilterOptions: function() {
      return JSON.parse($('.custom-list-default__default-source-list-plugin__options').val());
    },

    addFilter: function (event) {
      event.preventDefault();
      event.stopPropagation();

      var collection = this.collection;
      var filterOptions = this.getFilterOptions();

      // Getting first field ID and it's first operator.
      $.each(filterOptions.filter, function (filter_id) {
        $.each(filterOptions.filter[filter_id].operators, function (operator_id) {
          collection.create({
            filter_id: filter_id,
            operator: operator_id,
            value: ''
          });

          return false;
        });

        return false;
      });
    },

    render: function () {
      if (this.collection.length) {
        this.list.show();
      }
      else {
        this.list.hide();
      }

      return this;
    },

    addOne: function (insert_entry) {
      var view = new Drupal.custom_list_default.FilterList({model: insert_entry});

      this.$('.custom-list-default__filter-list').append(view.render().el);
    },

    addAll: function () {
      this.collection.each(this.addOne, this);
    },

    persistForm: function() {
      $('.custom-list-default__default-source-list-plugin__filter_selection').val(this.collection.toJSON());
    },

    clearForm: function() {
      var allModels = this.collection.models;
      var i;

      for (i=allModels.length - 1; i>=0; i--) {
        allModels[i].destroy();
      }

      this.persistForm();
    }
  });

})(jQuery, Drupal, Backbone);
