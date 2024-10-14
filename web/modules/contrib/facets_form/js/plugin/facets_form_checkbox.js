/**
 * @file
 * Support for 'facets_form' event dispatching for facets_form_checkbox plugin.
 */

(function (Drupal) {

  'use strict';

  /**
   * @type {Drupal.facetsForm.pluginClass}
   */
  Drupal.facetsFormCheckbox = {

    getEventName(widget) {
      return 'input';
    },

    setFilters(widget, dispatchEvent) {
      const facet = widget.dataset.drupalFacetsFormFacet;
      let filters = Array();
      widget.querySelectorAll('input[type="checkbox"]:checked').forEach(checkbox => {
        const name = checkbox.getAttribute('name');
        // Extract '123' from 'tags[123]'.
        const value = name.substring(facet.length + 1).slice(0, -1);
        filters.push(value);
      });
      widget.form.filters.setFilters(facet, filters, widget, dispatchEvent);
    }

  }

})(Drupal);
