/**
 * @file
 * Uses Jquery UI Sortable to sort entities in views.
 */

(function ($, sortable) {

  Drupal.sortableviews = Drupal.sortableviews || {};

  /**
   * Processes a single sortableview.
   *
   * Applies jquery.ui.sortable to the view contents and creates
   * a save button.
   */
  Drupal.sortableviews.processView = function ($view, viewSettings) {
    var $viewContent = $('.view-content', $view);
    var $sortableItemsContainer = (viewSettings.selector == 'self') ? $viewContent : $(viewSettings.selector, $viewContent);
    if (!$sortableItemsContainer.length) {
      return;
    }
    sortable.create($sortableItemsContainer[0], {
      handle: '.sortableviews-handle',
      animation: 120,
      onSort: function (event) {

        // Remove existing drupal messages within the view.
        $('.status-messages', $view).remove();

        // Create an array with the current order.
        viewSettings.current_order = [];
        $('.sortableviews-handle', $(this.el)).each(function (index, element) {
          viewSettings.current_order.push($(element).attr('data-id'));
        });

        // Create a clone of the settings object.
        var viewDataClone = $.extend({}, viewSettings);

        // Reverse the order if the sort order is descendant.
        if (viewDataClone.sort_order == 'desc') {
          viewDataClone.current_order = viewDataClone.current_order.reverse();
        }

        // Add the "Save changes" button.
        $('.sortableviews-save-changes', $view).html('').append($('<a>').attr({
          class: 'sortableviews-ajax-trigger button button--primary',
          id: Math.random().toString(36).slice(2),
          href: '#',
        }).html(Drupal.t('Save changes')).addDrupalAjax(viewDataClone.ajax_url, viewDataClone));
      }
    });
  }

  /**
   * A jQuery plugin that attaches ajax to an anchor.
   */
  $.fn.addDrupalAjax = function (ajaxUrl, dataToSubmit) {
    var ajaxSettings = {
      url : ajaxUrl,
      event: 'click',
      progress: {
        type: 'throbber',
      },
      setClick: true,
      submit : dataToSubmit,
      element: this[0],
    };
    Drupal.ajax[this.attr('id')] = new Drupal.ajax(ajaxSettings);
    return this;
  }

  /**
   * Attaches the table drag behavior to tables.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Processes all Sortable views in the page.
   */
  Drupal.behaviors.sortable = {
    attach: function (context, settings) {
      $.each(settings.sortableviews, function (viewDomId, viewSettings) {
        Drupal.sortableviews.processView($('.js-view-dom-id-' + viewDomId), viewSettings);
      });
    }
  };

})(jQuery, Sortable);
