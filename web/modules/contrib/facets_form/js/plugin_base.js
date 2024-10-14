/**
 * @file
 * Allows dispatching of 'facets_form' custom event.
 */

(function (Drupal, once) {

  'use strict';

  /**
   * @typedef {{getEventName(HTMLElement): string, setFilters(HTMLElement, boolean): void}} Drupal.facetsForm.pluginClass
   *
   * Interface for plugin Javascript snippets.
   */

  /**
   * Allows facets to dispatch an event when they're updating.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for facetsForm.
   */
  Drupal.behaviors.facetsForm = {
    attach(document) {
      // Switch to vanilla Javascript when support for Drupal 9.1 is dropped.
      // See https://www.drupal.org/node/3158256.
      const forms = once('facets_form', 'form[data-drupal-facets-form]', document);
      forms.forEach(function (form) {
        form.filters = new Drupal.facetsFormFilters();
      });

      const widgets = once('facets_form_widgets', '[data-drupal-facets-form-widget]', document);
      widgets.forEach(function (widget) {
        const camelCasePluginId = widget.dataset.drupalFacetsFormWidget.replace(/([-_]\w)/g, g => g[1].toUpperCase());
        const pluginNamespace = Drupal[camelCasePluginId];
        // Initialize the filters on page load but don't dispatch the event.
        pluginNamespace.setFilters(widget, false);
        widget.addEventListener(
          pluginNamespace.getEventName(widget), event => {
            pluginNamespace.setFilters(widget, true);
          }
        );
      });
    }
  };

  /**
   * Receives update info from widget plugins and dispatches the update event.
   */
  Drupal.facetsFormFilters = function () {

    /**
     * Filters storage
     *
     * @type {object}
     */
    let filters = {};

    /**
     * Returns all stored filters.
     *
     * @returns {Object}
     */
    this.all = () => { return filters; }

    /**
     * Stores a list of filters for a given facet.
     *
     * @param {string} facet
     *   The facet.
     * @param {Array} values
     *   The list of filters.
     * @param {HTMLElement} widget
     *   The widget.
     * @param {boolean} dispatchEvent
     *   Whether to dispatch the custom event.
     */
    this.setFilters = (facet, values, widget, dispatchEvent) => {
      const existingValues = filters[facet] !== undefined ? filters[facet] : Array();

      // Cast all values to string.
      // @see https://stackoverflow.com/questions/3445953/how-can-i-force-php-to-use-strings-for-array-keys
      values.forEach(value => {
        return value.toString();
      })
      // Don't handle empty values. These are "no filter for this facet" values.
      values = values.filter(value => value !== "");

      // Compute the changes.
      const diff = {
        added: values.filter(value => !existingValues.includes(value)),
        removed: existingValues.filter(value => !values.includes(value))
      };

      if (values.length > 0) {
        filters[facet] = values;
      }
      else {
        delete filters[facet];
      }

      if (dispatchEvent) {
        // Storage has been updated. Tell the World.
        widget.form.dispatchEvent(new CustomEvent('facets_form', {
          bubbles: true,
          detail: {
            facetsSource: widget.form.dataset.drupalFacetsForm,
            filters: filters,
            widget: widget,
            diff: diff
          }
        }));
      }
    }
  };

})(Drupal, once);
