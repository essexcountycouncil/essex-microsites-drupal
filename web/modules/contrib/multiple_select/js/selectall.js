/**
 * @file
 * Select all feature.
 */

(function (Drupal) {

  'use strict';

  let fields = document.querySelectorAll('[id^="multiple_select"]');
  if (fields !== null && fields.length > 0) {
    fields.forEach((checkbox) => {
      let id = checkbox.getAttribute('id');
      let selectorId = id.replace('multiple_select', 'edit').replace(/\_/g, '-');
      let checkboxes = document.querySelectorAll('#' + selectorId + ' .form-boolean--type-checkbox');

      let eleLabel = document.getElementById(id).nextElementSibling;
      eleLabel.setAttribute("for", id);
      checkbox.addEventListener('change', function (e) {
        // Update the checkboxes on select all click.
        checkboxes.forEach((item) => {
          if (checkbox.checked) {
            item.checked = true;
          }
          else {
            item.checked = false;
          }
          // Update the select all field.
          item.addEventListener('change', function () {
            let checked = document.querySelectorAll('#' + selectorId + ' .form-boolean--type-checkbox:checked');
            if (checked.length == checkboxes.length) {
              checkbox.checked = true;
            }
            else {
              checkbox.checked = false;
            }
          });
        });
      });
    });
  }

}(Drupal));
