/**
 * @file
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.honeypot = {};

  Drupal.honeypot.page_load_timestamp = new Date();

  Drupal.behaviors.honeypot_timestamp = {


    attach: function (context, settings) {
      var obj = this;
      var $honeypotTime = $('form.honeypot-timestamp-js').find('input[name="honeypot_time"]');

      $(once('honeypot-timestamp', 'form.honeypot-timestamp-js')).bind('submit', function () {
        $honeypotTime.attr('value', obj.getIntervalTimestamp());
      });
    },

    getIntervalTimestamp: function () {
      var now = new Date();
      var interval = Math.floor((now - Drupal.honeypot.page_load_timestamp) / 1000);
      return 'js_token:' + drupalSettings.honeypot.identifier + '|' + interval;
    }
  };

  if (Drupal.Ajax && Drupal.Ajax.prototype) {
    if (typeof Drupal.Ajax.prototype.beforeSubmit != 'undefined') {
      Drupal.Ajax.prototype.honeypotOriginalBeforeSubmit = Drupal.Ajax.prototype.beforeSubmit;
    }

    Drupal.Ajax.prototype.beforeSubmit = function (form_values, element, options) {

      if (this.$form && this.$form.hasClass('honeypot-timestamp-js')) {
        $.each(form_values, function(key, el) {
          // Inject the right interval timestamp.
          if (el.name == 'honeypot_time' && el.value == 'no_js_available') {
            form_values[key].value = Drupal.behaviors.honeypot_timestamp.getIntervalTimestamp();
          }
        });
      }

      if (typeof Drupal.Ajax.prototype.honeypotOriginalBeforeSubmit != 'undefined') {
        // Call the original function in case someone else has overridden it.
        return Drupal.Ajax.prototype.honeypotOriginalBeforeSubmit(form_values, element, options);
      }
    }
  }

}(jQuery, Drupal, drupalSettings));
