Drupal.behaviors.fathom = {
  attach: function (context, settings) {
    // Using once() to apply the effect when you want to run just one function.
    once('fathom', 'html', context).forEach(function (element) {
      element.classList.add('processed');
      const search = document.querySelector('#views-exposed-form-disposal-options-page-disposal-search');

      document.querySelector('#views-exposed-form-disposal-options-page-disposal-search').addEventListener('submit', () => {
        let item_input = document.querySelector('input[data-drupal-selector=edit-item]').value;
        fathom.trackEvent(`waste search: ${item_input}`);
      });

    });
  }
};
