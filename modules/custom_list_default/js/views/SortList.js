/**
 * @file SortList.js
 */

(function ($, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list.SortList = Backbone.View.extend({
    tagName: 'li',
    template: _.template(
      '<div class="custom-list-default__sort-row">' +
      '  <div class="custom-list-default__sort-row__sort_id"><%= tplSortSelector(sort_id) %></div>' +
      '  <div class="custom-list-default__sort-row__order">' +
      '    <select class="custom-list-default__sort-row__order_selection">' +
      '      <option value="DESC" <% if (order === "DESC") { %>selected<% } %>>Desc</option>' +
      '      <option value="ASC" <% if (order === "ASC") { %>selected<% } %>>Asc</option>' +
      '    </select>' +
      '  </div>' +
      '  <div><a class="custom-list-default__sort-row__remove">' + Drupal.t('Remove') + '</a></div>' +
      '</div>'
    ),

    templateHelpers: {
      tplSortSelector: function (selectedSortId) {
        // Create HTML for sort field selection.
        var select = '<select class="custom-list-default__sort-row__sort_id_selection">';
        $.each(this.sortOptions.sort, function (sortId, title) {
          select += '<option value="' + sortId + '" ' + ((sortId === selectedSortId) ? 'selected' : '') + '>' + title + '</option>';
        });
        select += '</select>';

        return select;
      }
    },

    events: {
      'click a.custom-list-default__sort-row__remove': 'removeModel',
      'change .custom-list-default__sort-row__order_selection': 'changeOrder',
      'change .custom-list-default__sort-row__sort_id_selection': 'changeField'
    },

    initialize: function () {
      this.listenTo(this.model, 'change', this.render);
      this.listenTo(this.model, 'destroy', this.remove);

      this.templateHelpers.sortOptions = JSON.parse($('.custom-list-default__default-source-list-plugin__options').val());

      this.persitElement = $('.custom-list-default__default-source-list-plugin__sort_selection');
    },

    persistCollection: function () {
      this.persitElement.val(JSON.stringify(this.model.collection.toJSON()));
    },

    render: function () {
      var data = this.model.toJSON();
      var render = this.template(_.extend(data, this.templateHelpers));

      this.persistCollection();

      this.$el.html(render);

      return this;
    },

    changeOrder: function (event) {
      var value = $(event.target).val();

      this.model.set('order', value);
      this.model.trigger('change', this.model);
    },

    changeField: function (event) {
      var value = $(event.target).val();

      this.model.set('sort_id', value);
      this.model.trigger('change', this.model);
    },

    removeModel: function () {
      // We need collection from model before we get values after destroying model.
      var collection = this.model.collection;

      this.model.destroy();

      this.persitElement.val(JSON.stringify(collection.toJSON()));
    }
  });

})(jQuery, Drupal, Backbone);
