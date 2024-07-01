/**
 * @file
 * Insert Hotjar script into page head.
 *
 * This script is supposed to be invoked by the EU Cookie compliance module once
 * agreement for the Hotjar cookie has been obtained.
 *
 * @see localgov_eu_cookie_compliance_page_attachments_alter().
 */

(function runDeferredScripts(jQuery, Drupal, drupalSettings) {

  var deferred_scripts = JSON.parse(drupalSettings.deferred_scripts || {});
  
  jQuery(deferred_scripts.hotjar_script_tag).appendTo('head');
})(jQuery, Drupal, drupalSettings)
