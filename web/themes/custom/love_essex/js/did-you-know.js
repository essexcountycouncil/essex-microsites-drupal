(function didYouKnow(Drupal) {
  Drupal.behaviors.didYouKnow = {
    attach: function (context) {
      // Find all Did You Know content.
      const dykContent = once('js-processed', '.paragraph--type--did-you-know', context);
      // Pick one randomly.
      if (dykContent.length > 0) {
        selectedDyk = Math.floor(Math.random() * dykContent.length);
        // Hide the rest.
        for (var i = 0; i < dykContent.length; i++) {
          if (i !== selectedDyk) {
            dykContent[i].remove();
          }
          else {
            dykContent[i].style.display = 'block';
          }
        }
      }
    },
  };
})(Drupal);
