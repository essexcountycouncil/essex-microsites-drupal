(function equalHeightsBlocksScript(Drupal) {
  Drupal.behaviors.equalHeightsBlocks = {
    attach: function (context) {
      context = context || document;

      const layouts = Array.from(context.querySelectorAll('.layout'));
      const layoutsWithIaBlocks = layouts.filter(item => item.querySelector('.ia-block'));
      const layoutsWithCallOutBox = layouts.filter(item => item.querySelector('.call-out-box'));
      const layoutsWithTeaserBlocks = layouts.filter(item => item.querySelector('.featured-teaser'));

      function equaliseHeightsOfTheseBlocks(layoutWithBlocks, typeOfBlock) {
        layoutWithBlocks.forEach(item => {
          const blocksToEqualiseHeights = item.querySelectorAll(typeOfBlock);
          const blockHeights = [];

          function handleGetHeights() {
            blocksToEqualiseHeights.forEach(block => {
              blockHeights.push(block.offsetHeight);
            });
            let tallestBlock = Math.max(...blockHeights);
            blocksToEqualiseHeights.forEach(block => {
              block.style.height = `${tallestBlock}px`;
            });
          }

          // We need a setTimeout here because the images take just
          // a tiny bit to load, which causes the layout to be set
          // before they are in place, then they get positioned
          // wrong on first load.
          setTimeout(() => {
            handleGetHeights();
          }, 250);
        })
      }

      layoutsWithCallOutBox.forEach(layout => {
        equaliseHeightsOfTheseBlocks(layoutsWithCallOutBox, '.call-out-box');
      });

      layoutsWithIaBlocks.forEach(layout => {
        equaliseHeightsOfTheseBlocks(layoutsWithIaBlocks, '.ia-block');
      });

      layoutsWithTeaserBlocks.forEach(layout => {
        equaliseHeightsOfTheseBlocks(layoutsWithTeaserBlocks, '.featured-teaser');
      });

    }
  };
}(Drupal));
