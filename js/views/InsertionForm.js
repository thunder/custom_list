/**
 * @file InsertionForm.js
 */

(function ($, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list.InsertionForm = Backbone.View.extend({
    template: _.template(
      '<div class="custom-list__insertion-form">' +
      '  <div>' + Drupal.t('Insertion List') + '</div>' +
      '  <ul class="custom-list__insertion-list"></ul>' +
      '</div>'
    ),

    initialize: function () {
      this.collection = new Backbone.Collection(null, {
        model: Drupal.custom_list.InsertionModel
      });

      this.listenTo(this.collection, 'add', this.addOne);
      this.listenTo(this.collection, 'reset', this.addAll);
      this.listenTo(this.collection, 'all', this.render);

      this.list = this.$('.custom-list__insertion-list');

      this.$el.html(this.template());

      // Load existing data.
      var dataElement = $('*[name="settings[custom_list_config_form][insertion_form][insert_selection]"]');
      this.collection.set(JSON.parse(dataElement.val()));
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
      var view = new Drupal.custom_list.InsertionList({model: insert_entry});

      this.$('.custom-list__insertion-list').append(view.render().el);
    },

    addAll: function () {
      this.collection.each(this.addOne, this);
    }
  });

})(jQuery, Drupal, Backbone);
