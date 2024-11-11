(function (Drupal) {
  Drupal.behaviors.eccMap = {
    attach(context) {

      const mapElementsArray = once('map-processed', '.field--name-field-p-map-locations-data', context);
      if (mapElementsArray.length > 0) {
        for (var i = 0; i < mapElementsArray.length; i++) {
          let mapElement = mapElementsArray[i];
          console.log(mapElement);
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

          const eccLocations = mapElement.querySelectorAll('.field--name-field-p-map-locations-data > .field__item');

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
            var locationLink = null;
            if (eccLocation.querySelector('.field--name-field-map-location-link') != null) {
              locationLink = eccLocation.querySelector('.field--name-field-map-location-link a').href;
            }
            var locationDescription = null;
            if (eccLocation.querySelector('.field--name-field-p-map-location-description') != null) {
              locationDescription = eccLocation.querySelector('.field--name-field-p-map-location-description').textContent;
            }

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
                  numberDiv.appendChild(image);
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
                map.setZoom(14);
                map.panTo(this.getLatLng());
              });
              marker.addEventListener('popupopen', function () {
                var popup_close = document.querySelector('.leaflet-popup-close-button');
                popup_close.addEventListener('click', function () {
                  var bounds = L.latLngBounds(latlongs).pad(0.1);
                  map.fitBounds(bounds);
                });
              });
              var popupContent = '';
              if (locationLink != null) {
                popupContent = `<h4><a href="${locationLink.trim()}">${locationTitle.trim()}</a></h4>`;
              }
              else {
                popupContent = `<h4>${locationTitle.trim()}</h4>`;
              }
              if (locationDescription != null) {
                popupContent += `<p>${locationDescription.trim()}</p>`;
              }
              marker.bindPopup(popupContent);

              // Adding each marker to an array of markers
              markers.push(marker);
            }
          });
          // Zoom the map to fit the locations.
          var bounds = L.latLngBounds(latlongs).pad(0.1);
          map.fitBounds(bounds);
          if (latlongs.length === 1) {
            map.setZoom(10);
          }
        }
      }
    },
  };
})(Drupal);
