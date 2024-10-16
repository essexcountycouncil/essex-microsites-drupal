/**
 * @file
 * Test JS snippet.
 */

(function (Drupal) {

  'use strict';

  Drupal.behaviors.facetsFormTest = {
    attach(context) {
     let counter = 1;
      let div = document.querySelector('div#facets-form-test');
      if (div === null) {
        const form = document.querySelector('form[data-drupal-facets-form]');
        div = document.createElement('div');
        div.setAttribute('id', 'facets-form-test');
        form.parentNode.insertBefore(div, form.nextSibling);
      }
      context.addEventListener('facets_form', event => {
        const testElement = document.createElement('div');
        testElement.setAttribute('id', 'test-' + counter++);
        const testElementText = document.createTextNode(JSON.stringify({
          filters: event.detail.filters,
          diff: event.detail.diff,
        }));
        testElement.appendChild(testElementText);
        div.innerHTML = testElement.outerHTML;
      });
    }
  };

})(Drupal);
