/**
 * @file
 * Support for facets_form event dispatching for facets_form_date_range plugin.
 */

(function (Drupal) {

  'use strict';

  /**
   * @type {Drupal.facetsForm.pluginClass}
   */
  Drupal.facetsFormDateRange = {

    getEventName(widget) {
      return 'change';
    },

    setFilters(widget, dispatchEvent) {
      const facet = widget.dataset.drupalFacetsFormFacet;
      let filters = Array();

      let range = Array();
      ['from', 'to'].forEach(field => {
        let limit = widget.querySelector('input[type="date"][name="' + facet + '[' + field +'][date]"]').value;
        const time = widget.querySelector('input[type="time"][name="' + facet + '[' + field +'][time]"]');
        if (limit && time && time.value) {
          limit += 'T' + time.value + time.dataset.timezone.replace('+', '%2B');
        }
        range.push(limit);
      });

      // @todo Pass delimiter from backend.
      range = range.join('~');
      if (range !== '~') {
        filters.push(range);
      }

      widget.form.filters.setFilters(facet, filters, widget, dispatchEvent);
    }
  }

})(Drupal);
