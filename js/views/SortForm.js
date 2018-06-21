/**
 * @file SortForm.js
 */

(function ($, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list.SortForm = Backbone.View.extend({
    template: _.template(
      '<div class="custom-list__sort-form">' +
      '  <div>' + Drupal.t('Sort List') + '</div>' +
      '  <div class="custom-list__sort-form__header">' +
      '    <div>' + Drupal.t('Sort by') + '</div>' +
      '    <div>' + Drupal.t('Order') + '</div>' +
      '    <div>' + Drupal.t('Actions') + '</div>' +
      '  </div>' +
      '  <ul class="custom-list__sort-list"></ul>' +
      '  <input type="submit" class="button custom-list__sort__add-button" value="' + Drupal.t('Add sort option') + '" />' +
      '</div>'
    ),

    events: {
      'click .custom-list__sort__add-button': 'addSort'
    },

    initialize: function () {
      this.collection = new Backbone.Collection(null, {
        model: Drupal.custom_list.SortModel
      });

      this.listenTo(this.collection, 'add', this.addOne);
      this.listenTo(this.collection, 'reset', this.addAll);
      this.listenTo(this.collection, 'all', this.render);

      this.list = this.$('.custom-list__sort-list');

      this.sortOptions = JSON.parse($('*[name="settings[custom_list_config_form][options]"]').val());

      this.$el.html(this.template());

      // Load existing data.
      var dataElement = $('*[name="settings[custom_list_config_form][sort_selection]"]');
      this.collection.set(JSON.parse(dataElement.val()));
    },

    addSort: function (event) {
      event.preventDefault();
      event.stopPropagation();

      var collection = this.collection;
      $.each(this.sortOptions.sort, function (key) {
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
      var view = new Drupal.custom_list.SortList({model: insert_entry});

      this.$('.custom-list__sort-list').append(view.render().el);
    },

    addAll: function () {
      this.collection.each(this.addOne, this);
    }
  });

})(jQuery, Drupal, Backbone);
