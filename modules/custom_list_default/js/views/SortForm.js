/**
 * @file SortForm.js
 */

(function ($, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list_default.SortForm = Backbone.View.extend({
    template: _.template(
      '<div class="custom-list-default__sort-form">' +
      '  <div>' + Drupal.t('Sort List') + '</div>' +
      '  <div class="custom-list-default__sort-form__header">' +
      '    <div>' + Drupal.t('Sort by') + '</div>' +
      '    <div>' + Drupal.t('Order') + '</div>' +
      '    <div>' + Drupal.t('Actions') + '</div>' +
      '  </div>' +
      '  <ul class="custom-list-default__sort-list"></ul>' +
      '  <input type="submit" class="button custom-list-default__sort__add-button" value="' + Drupal.t('Add sort option') + '" />' +
      '</div>'
    ),

    events: {
      'click .custom-list-default__sort__add-button': 'addSort'
    },

    initialize: function () {
      this.collection = new Backbone.Collection(null, {
        model: Drupal.custom_list_default.SortModel
      });

      this.listenTo(this.collection, 'add', this.addOne);
      this.listenTo(this.collection, 'reset', this.addAll);
      this.listenTo(this.collection, 'all', this.render);

      this.list = this.$('.custom-list-default__sort-list');

      this.setElement(this.template());

      // Load existing data.
      var dataElement = $('.custom-list-default__default-source-list-plugin__sort_selection');
      this.collection.set(JSON.parse(dataElement.val()));
    },

    getSortOptions: function() {
      return JSON.parse($('.custom-list-default__default-source-list-plugin__options').val());
    },

    addSort: function (event) {
      event.preventDefault();
      event.stopPropagation();

      var collection = this.collection;
      var sortOptions = this.getSortOptions();

      $.each(sortOptions.sort, function (key) {
        collection.create({
          sort_id: key,
          order: 'DESC'
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
      var view = new Drupal.custom_list_default.SortList({model: insert_entry});

      this.$('.custom-list-default__sort-list').append(view.render().el);
    },

    addAll: function () {
      this.collection.each(this.addOne, this);
    },

    persistForm: function() {
      $('.custom-list-default__default-source-list-plugin__sort_selection').val(this.collection.toJSON());
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
