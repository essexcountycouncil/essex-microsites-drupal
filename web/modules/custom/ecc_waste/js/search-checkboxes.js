Drupal.behaviors.searchCheckboxes = {
  attach: function (context, settings) {
    //Using once() to apply the selectA11y effect when you want to run just one function.
    once('searchCheckboxes', 'html', context).forEach(function (element) {
      element.classList.add('processed');


      const search = document.querySelector('.form-item--checkbox-search input.form-text');

      const labels = document.querySelectorAll("#edit-field-disposal-option-items .form-type--checkbox label");

      search.addEventListener("input", () => Array.from(labels).forEach((element) => {
        console.log(element);
        let labelString = String(element.textContent);
        labelString = labelString.toLowerCase();
        element.style.display = labelString.toLowerCase().includes(search.value.toLowerCase()) ? "inline" : "none"
        element.previousElementSibling.style.display = element.style.display;
      }));

    });
  }
};

// element.style.display = element.childNodes[1].id.

//  element.childNodes.textContent.toLowerCase().includes(search.value.toLowerCase()) ? "inline" : "none" )
