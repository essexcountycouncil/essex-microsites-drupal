/**
 * @file JS file for the directory facets components.
 */

(function directoryFacetsScript(Drupal) {
  Drupal.behaviors.directoryFacets = {
    attach: function (context) {
      const directoryFacetsBlocks = once("directory-facets-block", ".directory-facets-block", context);

      if (directoryFacetsBlocks) {
        directoryFacetsBlocks.forEach((item) => {
          const windowWidth = window.innerWidth;
          if (windowWidth < 768) {
            const randomId = Math.floor(Math.random() * 1000);
            const button = document.createElement("button");
            button.classList.add("directory-facets-block__button");

            const filtersClosedButtonContent = `
              <span class="directory-facets-block__button-icon">⏵</span>
              <span class="directory-facets-block__button-text">${Drupal.t("Filter")}</span>
            `;
            const filtersOpenedButtonContent = `
              <span class="directory-facets-block__button-icon">⏵</span>
              <span class="directory-facets-block__button-text">${Drupal.t("Hide Filter")}</span>
            `;

            button.innerHTML = filtersClosedButtonContent;
            button.setAttribute("aria-controls", `directory-facets-block__content-${randomId}`
            );
            button.setAttribute("aria-expanded", "false");
            item.insertBefore(button, item.firstChild);

            const facetsContent = item.querySelector(".directory-facets-block__content");
            facetsContent.id = `directory-facets-block__content-${randomId}`;
            facetsContent.setAttribute("aria-hidden", "true");
            facetsContent.style.display = "none";

            button.addEventListener("click", () => {
              const expanded = button.getAttribute("aria-expanded") === "true";
              button.setAttribute("aria-expanded", !expanded);
              facetsContent.setAttribute("aria-hidden", expanded);
              facetsContent.style.display = expanded ? "none" : "block";
              buttonText = expanded ? "Filter" : "Hide filter";
              button.innerHTML = expanded ? filtersClosedButtonContent : filtersOpenedButtonContent;
            });
          }
        });
      }

      // Currently you see no results when you do a search, because they are
      // down the page a bit. This will scroll the user to the map when they
      // search.
      const url = window.location;
      // If url contains a ? query string, then we know that the user has
      // searched for something
      if (url.search) {
        // Scroll to map.
        const map = document.querySelector("#leaflet-map-view-localgov-directory-channel-embed-map");
        map.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }

    },
  };
})(Drupal);
