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

            // Numbers that are being used on the markers and location cards.
            const locationOrderNumber = index + 1;
            const locationOrderNumberAsString = String(locationOrderNumber);

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

            marker.bindPopup(`<a href="/">${locationTitle.trim()}</a>`);

            // Adding each marker to an array of markers
            markers.push(marker);
          }
        });
        // Zoom the map to fit the locations.
        var bounds = L.latLngBounds(latlongs).pad(0.1);
        map.fitBounds(bounds);
      }
    },
  };
})(Drupal);
