/**
 * @file InsertionList.js
 */

(function ($, Drupal, Backbone) {

  'use strict';

  Drupal.custom_list.InsertionList = Backbone.View.extend({
    tagName: 'li',
    template: _.template(
      '<div class="custom-list__insertion-row">' +
      '  <% if (type === "entity") { %>' +
      '    <div class="custom-list__insertion-row__position"><%- position+1 %></div>' +
      '    <div><%- config.name %></div>' +
      '    <div class="custom-list__insertion-row__view-mode"><%- config.view_mode %></div>' +
      '    <div><a class="custom-list__insertion-row__remove">' + Drupal.t('Remove') + '</a></div>' +
      '    <input class="custom-list__insertion-row__edit-view-mode" style="display: none;" type="text" value="<%- config.view_mode %>" />' +
      '  <% } else { %>' +
      '    <div class="custom-list__insertion-row__position"><%- position+1 %></div>' +
      '    <div><%- config.config.label %>&nbsp;(<%- config.config.provider %>)</div>' +
      '    <div class="custom-list__insertion-row__view-mode">-</div>' +
      '    <div><a class="custom-list__insertion-row__remove">' + Drupal.t('Remove') + '</a></div>' +
      '  <% } %>' +
      '</div>' +
      '<input class="custom-list__insertion-row__edit-position" style="display: none;" type="text" value="<%- position+1 %>" />'
    ),

    events: {
      'click a.custom-list__insertion-row__remove': 'removeModel',
      'click .custom-list__insertion-row__position': 'editPosition',
      'keypress .custom-list__insertion-row__edit-position': 'updatePositionOnEnter',
      'blur .custom-list__insertion-row__edit-position': 'closeEditPosition',
      'click .custom-list__insertion-row__view-mode': 'editViewMode',
      'keypress .custom-list__insertion-row__edit-view-mode': 'updateViewModeOnEnter',
      'blur .custom-list__insertion-row__edit-view-mode': 'closeEditViewMode'
    },

    initialize: function () {
      this.listenTo(this.model, 'change', this.render);
      this.listenTo(this.model, 'destroy', this.remove);

      this.persitElement = $('*[name="settings[custom_list_config_form][insertion_form][insert_selection]"]');
    },

    persistCollection: function () {
      this.persitElement.val(JSON.stringify(this.model.collection.toJSON()));
    },

    render: function () {
      var render = this.template(this.model.toJSON());

      this.persistCollection();

      this.$el.html(render);
      this.inputPosition = this.$('.custom-list__insertion-row__edit-position');
      this.inputViewMode = this.$('.custom-list__insertion-row__edit-view-mode');

      return this;
    },

    editPosition: function (event) {
      var $target = $(event.target);

      var cssConfig = $target.position();
      cssConfig.top = cssConfig.top - 1;
      cssConfig.width = $target.width();
      cssConfig.height = $target.height() + 2;

      this.inputPosition.css(cssConfig).show().focus().select();
    },

    editViewMode: function (event) {
      var $target = $(event.target);

      var cssConfig = $target.position();
      cssConfig.top = cssConfig.top - 1;
      cssConfig.width = $target.width();
      cssConfig.height = $target.height() + 2;

      this.inputViewMode.css(cssConfig).show().focus().select();
    },

    closeEditPosition: function () {
      var value = this.inputPosition.val();

      if (!value) {
        this.clear();
      }
      else {
        this.model.set('position', parseInt(value, 10) - 1);
      }

      this.inputPosition.hide();
    },

    closeEditViewMode: function () {
      var value = this.inputViewMode.val();

      if (!value) {
        this.clear();
      }
      else {
        var config = this.model.get('config');
        config.view_mode = value;

        this.model.set('config', config);
        this.model.trigger('change', this.model);
      }

      this.inputViewMode.hide();
    },

    updatePositionOnEnter: function (e) {
      if (e.keyCode === 13) {
        e.stopPropagation();
        e.preventDefault();

        this.closeEditPosition();
      }
    },

    updateViewModeOnEnter: function (e) {
      if (e.keyCode === 13) {
        e.stopPropagation();
        e.preventDefault();

        this.closeEditViewMode();
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
