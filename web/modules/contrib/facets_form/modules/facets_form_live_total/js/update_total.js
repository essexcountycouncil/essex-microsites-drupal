/**
 * @file
 * Updates the Facets Summary total.
 */

(function (Drupal, once) {

  'use strict';

  /**
   * Registers an event listener that updates the total for each facets form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for facetsFormLiveTotal.
   */
  Drupal.behaviors.facetsFormLiveTotal = {
    attach(document) {
      // Switch to vanilla Javascript when support for Drupal 9.1 is dropped.
      // See https://www.drupal.org/node/3158256.
      const forms = once('facets_form_live_total', 'form[data-drupal-facets-form-live-total]', document);
      forms.forEach(function (form) {
        form.addEventListener('facets_form', event => {
          if (Drupal.facetsFormLiveTotal.updateIsNeeded(event.detail.widget, event.detail.diff)) {
            Drupal.ajax({
              url: Drupal.facetsFormLiveTotal.buildUrl(event.detail.facetsSource, event.detail.filters)
            }).execute();
          }
        })
      });
    }
  };

  Drupal.facetsFormLiveTotal = {

    /**
     * Checks whether a total update is needed.
     *
     * For instance, if a checkbox has at least one of its ancestors selected,
     * total update has no effect and useless Ajax request is avoided.
     *
     * @param {HTMLElement} widget
     *   The widget that fired the total update.
     * @param {{added, removed: any[]}} diff
     *   Object with two keys:
     *   - added: An array of items just added to widget.
     *   - removed: An array of items just removed from widget.
     *
     * @returns {boolean}
     *   Whether a total update is needed.
     */
    updateIsNeeded: (widget, diff) => {
      let updateTotal = true;
      let ancestors = widget.dataset.drupalFacetsFormAncestors;

      // A widget not defining ancestors should always trigger update.
      if (ancestors === undefined || ancestors === '[]') {
        return updateTotal;
      }
      ancestors = JSON.parse(ancestors);

      const facet = widget.dataset.drupalFacetsFormFacet;

      // Scan all recent changed filters and determine if they have ancestors.
      // If yes, check if, at least, one of them is enabled. If yes, then this
      // event should not trigger a total update as the value won't change so it
      // would be a useless Ajax request.
      const methods = Object.keys(diff);
      for (const methodIndex in methods) {
        for (const valueIndex in diff[methods[methodIndex]]) {
          const value = diff[methods[methodIndex]][valueIndex];
          if (ancestors[value] !== undefined) {
            const allFilters = widget.form.filters.all();
            const filters = facet in allFilters ? allFilters[facet] : [];
            for (const ancestorKey in ancestors[value]) {
              if (filters.includes(ancestors[value][ancestorKey])) {
                updateTotal = false;
                break;
              }
            }
          }
        }
      }

      return updateTotal;
    },

    /**
     * Builds a full URL for Ajax request.
     *
     * @param {string} facetsSource
     *   The facets source plugin ID.
     * @param {object} filters
     *   Keys are facet plugin IDs, values array of selected filters.
     *
     * @returns {string}
     *   The full Ajax request URL.
     */
    buildUrl: (facetsSource, filters) => {
      let queryString = Array('facets_source=' + facetsSource);
      let i = 0;
      Object.keys(filters).forEach(facet => {
        filters[facet].forEach(value => {
          queryString.push('f[' + i++ + ']=' + facet + ':' + value);
        });
      });
      return Drupal.url('facets-form-live-total?' + queryString.join('&'));
    }
  };

})(Drupal, once);
