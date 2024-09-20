(function (Drupal) {
  Drupal.behaviors.eccMap = {
    attach(context) {

      const mapElementsArray = once('map-processed', '.field--name-field-p-map-locations-data', context);

      if (mapElementsArray.length > 0) {

        const mapElement = mapElementsArray[0];

        // Define an element which contains a map.
        let map = L.map(mapElement, {
          center: [53.3239919, -6.5258808],
          zoom: 13,
          dragging: !L.Browser.mobile,
          scrollWheelZoom: false,
        });

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        const eccLocations = document.querySelectorAll('.field--name-field-p-map-locations-data > .field__item');

        // This is a variable where all markers are stored.
        let markers = [];

        // This variable is going to be used later on for a calculation of bounds.
        let activeMarkers = [];

        // // Show More functionality.
        // const maxShopsPerPage = 9
        // const hiddenShop = 'js-hidden-shop';

        // // Classes for different button stylings
        // const buttonTransparentWhiteText = 'button-color--transparent-white-text-borders';
        // const buttonTransparentWhiteAltText = 'button-color--transparent-white-alt-text-borders';
        // const buttonTransparentGreenText = 'button-color--transparent-green-text-borders';
        // const buttonActive = 'js-button-active';

        // // Classes for different type of Oxfam shops location cards
        // const shopLocation = 'location-card__shops';
        // const donationPointLocation = 'location-card__donation-point';
        // const donationPointLocationActive = 'js-location-card__donation-point--active';
        // const shopLocationActive = 'js-location-card__shop--active';

        // // We are setting icons for locations; different based on the type of
        // // the Oxfam shop and the state (active or non-active)
        const markerIcon = '/modules/custom/ecc_map/images/map-marker-01-min.png';

        var latlongs = [];
        eccLocations.forEach((eccLocation, index) => {

          let marker;
          const latitude = eccLocation.querySelector('.latlon-lat').textContent;
          const longitude = eccLocation.querySelector('.latlon-lon').textContent;
          const locationTitle = eccLocation.querySelector('.field--name-field-p-map-location-title').textContent;
          console.log(locationTitle);
          if (latitude != null && longitude != null && locationTitle != null) {
            latlongs.push([latitude, longitude]);
            // const shopType = eccLocation.querySelector('.location-card__shop-type');
            // const shopUrl = eccLocation.querySelector('.location-card__details');

            // This is needed for the different icons, for different Oxfam shop types.
            // But we are also setting a default value, just in case some Oxfam shop
            // doesn't have shop type defined.
            // const shopTypeText = shopType ? shopType.textContent : 'shops';
            // const shopTypeString = String(shopTypeText).replace(/\s/g, '').toLowerCase();

            // Numbers that are being used on the markers and location cards.
            const locationOrderNumber = index + 1;
            const locationOrderNumberAsString = String(locationOrderNumber);

            // Show on map button.
            const showOnMap = eccLocation.querySelector('.location-card__show-on-map');

            // const shopTypeVar = shopTypeString === 'shops' ? shopIcon : donationPointIcon;

            // Markers with numbers.
            L.NumberedDivIcon = L.Icon.extend({
              options: {
                iconUrl: markerIcon,
                iconSize: new L.Point(42, 70),
                iconAnchor: new L.Point(20, 50),
                popupAnchor: new L.Point(2, -50),
                number: '',
                shadowUrl: null,
                className: 'leaflet-marker-number__wrapper'
              },

              createIcon: function () {
                let numberDiv = document.createElement('div');
                let image = this._createImg(this.options['iconUrl']);
                image.setAttribute('alt', locationTitle.trim())
                let innerNumberDiv = document.createElement('div');
                innerNumberDiv.setAttribute('class', 'marker-number');
                innerNumberDiv.innerHTML = this.options['number'] || '';
                numberDiv.appendChild(image);
                numberDiv.appendChild(innerNumberDiv);
                this._setIconStyles(numberDiv, 'icon');
                return numberDiv;
              },
            });

            marker = new L.marker([latitude, longitude], {
              title: locationTitle.trim(),
              riseOnHover: true,
              icon: new L.NumberedDivIcon({ number: locationOrderNumberAsString }),
            })
              .addTo(map)

            marker.addEventListener('click', function () {
              markers.forEach((markerItem) => {
                if (markerItem.zIndexOffset === 999) {
                  markerItem.setZIndexOffset(0);
                }
              });

              this.setZIndexOffset(999);
              map.setZoom(18);
              map.panTo(this.getLatLng());
            });

            // We want to use link for each Oxfam shop/donation point,
            // so we are wrapping the name of the shop inside of the <a> element
            // with the correct URL we got by pulling the value from the
            // ".location-card__details" of each shop card listed in the View.
            // ${shopUrl.href}
            marker.bindPopup(`<a href="/">${locationTitle.trim()}</a>`);

            // Adding each marker to an array of markers
            markers.push(marker);

            // showOnMap.addEventListener('click', function () {
            //   if (showOnMap.classList.contains('js-button-active')) {
            //     // If the card is already active, just scroll to top of the page and show the marker on the map.
            //     window.scrollTo(0, 0);
            //     marker.fire('click');
            //   } else {
            //     // If the location card is not active, then 'deactivate' previously 'activated' location cards
            //     // and activate the location card we clicked on at this moment.
            //     const oxfamShopFinderElement = document.querySelector('.view--oxfam-shop-finder');
            //     const locationCardTransparentGreenButtons = oxfamShopFinderElement.querySelectorAll('.' + buttonTransparentWhiteText);
            //     const locationCardTransparentDarkBlueButtons = oxfamShopFinderElement.querySelectorAll('.' + buttonTransparentWhiteAltText);

            //     // We need to reset the styling of previously clicked buttons (based on type of location card).
            //     if (locationCardTransparentGreenButtons) {
            //       locationCardTransparentGreenButtons.forEach((locationCardTransparentGreenButton) => {
            //         locationCardTransparentGreenButton.classList.remove(buttonTransparentWhiteText);
            //         locationCardTransparentGreenButton.classList.add(buttonTransparentGreenText);
            //       });
            //     }

            //     if (locationCardTransparentDarkBlueButtons) {
            //       locationCardTransparentDarkBlueButtons.forEach((locationCardTransparentDarkBlueButton) => {
            //         locationCardTransparentDarkBlueButton.classList.remove(buttonTransparentWhiteAltText);
            //         locationCardTransparentDarkBlueButton.classList.add(buttonTransparentGreenText);
            //       });
            //     }

            //     // First, we need to check if there is an already active location card and remove active class
            //     // from the card itself and corresponding button.
            //     // There can be only ONE previously active location card.
            //     const activeDonationPoint = document.querySelector('.' + donationPointLocationActive);
            //     if (activeDonationPoint) {
            //       const showOnMapButtonActive = activeDonationPoint.querySelector('.' + buttonActive);
            //       activeDonationPoint.classList.remove(donationPointLocationActive);
            //       showOnMapButtonActive.classList.remove(buttonActive);
            //     }

            //     const activeShop = document.querySelector('.' + shopLocationActive);
            //     if (activeShop) {
            //       const showOnMapButtonActive = activeShop.querySelector('.' + buttonActive);
            //       activeShop.classList.remove(shopLocationActive);
            //       showOnMapButtonActive.classList.remove(buttonActive);
            //     }

            //     // We are adding now 'active' classes so the change of style of active location card is visible.
            //     if (eccLocation.classList.contains(donationPointLocation)) {
            //       eccLocation.classList.add(donationPointLocationActive);
            //       showOnMap.classList.add(buttonActive);
            //       const locationCardButtons = showOnMap.parentElement.querySelectorAll('.' + buttonTransparentGreenText);

            //       locationCardButtons.forEach((locationCardButton) => {
            //         locationCardButton.classList.remove(buttonTransparentGreenText);
            //         locationCardButton.classList.add(buttonTransparentWhiteAltText);
            //       });
            //     }

            //     if (eccLocation.classList.contains(shopLocation)) {
            //       eccLocation.classList.add(shopLocationActive);
            //       showOnMap.classList.add(buttonActive);
            //       const locationCardButtons = showOnMap.parentElement.querySelectorAll('.' + buttonTransparentGreenText);

            //       locationCardButtons.forEach((locationCardButton) => {
            //         locationCardButton.classList.remove(buttonTransparentGreenText);
            //         locationCardButton.classList.add(buttonTransparentWhiteText);
            //       });
            //     }

            //     window.scrollTo(0, 0);
            //     marker.fire('click');
            //   }
            // });
          }
        });
        // Zoom the map to fit the locations.
        var bounds = L.latLngBounds(latlongs).pad(0.1);
        map.fitBounds(bounds);
      }
    },
  };
})(Drupal);
