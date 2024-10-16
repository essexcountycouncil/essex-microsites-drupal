/**
 * @file
 * Support for 'facets_form' event dispatching for facets_form_dropdown plugin.
 */

(function (Drupal) {

  'use strict';

  /**
   * @type {Drupal.facetsForm.pluginClass}
   */
  Drupal.facetsFormDropdown = {

    getEventName(widget) {
      return 'change';
    },

    setFilters(widget, dispatchEvent) {
      let filters = Array();
      if (widget.selectedOptions.length > 0) {
        Object.keys(widget.selectedOptions).forEach(index => {
          const option = widget.selectedOptions[index];
          filters.push(option.getAttribute('value'));
        });
      }
      const facet = widget.dataset.drupalFacetsFormFacet;
      widget.form.filters.setFilters(facet, filters, widget, dispatchEvent);
    }
  }

})(Drupal);
