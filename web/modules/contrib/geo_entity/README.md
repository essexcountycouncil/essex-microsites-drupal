# Geo Entity

Provides a entity for storing, and reusing, geographic information.

Pre-configured to use openstreetmap tiles, and geocoder openstreetmap backend.
The intention is that this can be exchanged for preferred services. More detail
about gecoder options can be found on the [Installing new Geocoders page](https://github.com/localgovdrupal/geo_entity/wiki/Installing-new-Geocoders-%5BFAQ:-%22Why-doesn't-it-find-...%22%5D)

There are two default bundle types, address and area.

There is [an overview of the bundles and reusing addresses on the wiki](https://github.com/localgovdrupal/geo_entity/wiki/Locations-Module-(LocalGov-Geo)).

### Good to know
- There is a [known issue](https://www.drupal.org/project/geocoder/issues/3153678#comment-14203727) with Drupal geocoder plugins that they sometimes do not immediately appear in the *Geocoder provider* dropdown at /admin/config/system/geocoder/geocoder-provider.  If this happens, restart PHP or the (virtual) machine hosting PHP and then review the dropdown.

## Maintainers

Current maintainers: 

 - Ekes https://drupal.org/u/ekes
 - Finn: https://drupal.org/u/finn-lewis
 
