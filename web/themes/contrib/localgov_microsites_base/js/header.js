(function headerScript(Drupal) {
  Drupal.behaviors.micrositeHeader = {
    attach: function (context, settings) {
      context = context || document;

      const offCanvas = context.querySelector("#off-canvas");
      const offCanvasToggle = context.querySelector(
        ".microsite-header__off-canvas-toggle"
      );
      const offCanvasClose = context.querySelector(".off-canvas__close");

      if (offCanvas) {
        const focusable = offCanvas.querySelectorAll(
          'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );

        function toggleStates() {
          toggleState = offCanvasToggle.getAttribute("aria-expanded");
          offCanvasState = offCanvas.getAttribute("data-expanded");
        }

        function handleOffCanvas() {
          toggleStates();
          if (toggleState === "true" || offCanvasState === "true") {
            handleCloseOffCanvas();
          } else {
            handleOpenOffCanvas();
          }
        }

        function handleOpenOffCanvas() {
          toggleStates();
          offCanvasToggle.setAttribute("aria-expanded", "true");
          offCanvas.setAttribute("data-expanded", "true");
          focusable[0].focus();
        }

        function handleCloseOffCanvas() {
          toggleStates();
          offCanvasToggle.focus();
          offCanvasToggle.setAttribute("aria-expanded", "false");
          offCanvas.setAttribute("data-expanded", "false");
        }

        function trapFocusInOffCanvasArea(element) {
          const firstFocusableElement = focusable[0];
          const lastFocusableElement = focusable[focusable.length - 1];

          element.addEventListener("keydown", function (e) {
            const isTabPressed = e.code === "Tab";
            if (!isTabPressed) {
              return;
            }
            /* shift + tab */
            if (e.shiftKey) {
              if (context.activeElement === firstFocusableElement) {
                lastFocusableElement.focus();
                e.preventDefault();
              }
            } else {
              /* tab */
              if (context.activeElement === lastFocusableElement) {
                firstFocusableElement.focus();
                e.preventDefault();
              }
            }
          });
        }

        if (!offCanvasToggle) {
          return;
        } else {
          offCanvasToggle.addEventListener("click", handleOffCanvas);
          window.addEventListener("keyup", function (e) {
            if (e.code === "Escape") {
              handleCloseOffCanvas();
            }
          });
          trapFocusInOffCanvasArea(offCanvas);
        }
        offCanvasClose.addEventListener("click", () => {
          handleCloseOffCanvas();
        });
      }
    },
  };
})(Drupal);
