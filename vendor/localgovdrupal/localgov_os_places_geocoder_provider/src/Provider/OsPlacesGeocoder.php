<?php

declare(strict_types = 1);

namespace LocalgovDrupal\OsPlacesGeocoder\Provider;

use Geocoder\Collection as AddressCollectionInterface;
use Geocoder\Exception\InvalidServerResponse;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Http\Provider\AbstractHttpProvider;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Provider as GeocoderProviderInterface;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use LocalgovDrupal\OsPlacesGeocoder\Model\OsPlacesAddress;

/**
 * Geocoder plugin for the Ordnance Survey Places API.
 *
 * Lists addresses based on given postcode or address string.  Addresses are
 * keyed by their UPRN.
 *
 * @see https://osdatahub.os.uk/docs/places/overview
 * @see https://en.wikipedia.org/wiki/Unique_Property_Reference_Number
 */
class OsPlacesGeocoder extends AbstractHttpProvider implements GeocoderProviderInterface {

  /**
   * Keeps track of Geocoding parameters.
   */
  public function __construct(HttpClientInterface $http_client, string $generic_address_query_url, string $postcode_query_url, string $api_key, string $user_agent = 'LocalGov Drupal') {

    parent::__construct($http_client);

    $this->genericAddressQueryUrl = $generic_address_query_url;
    $this->postcodeQueryUrl       = $postcode_query_url;
    $this->apiKey                 = $api_key;
    $this->userAgent              = $user_agent;
  }

  /**
   * Provider name.
   */
  public function getName() :string {

    return 'localgov-ordnance-survey-places';
  }

  /**
   * Address lookup.
   *
   * Given a postcode or part of a street address, returns all the matching
   * addresses.
   */
  public function geocodeQuery(GeocodeQuery $query) :AddressCollectionInterface {

    $api_endpoint = $this->genericAddressQueryUrl;
    $query_text   = trim($query->getText());
    $search_query = ['query' => $query_text];

    if (self::isPostcode($query_text)) {
      $api_endpoint = $this->postcodeQueryUrl;
      $search_query = ['postcode' => $query_text];
    }

    $search_query['output_srs'] = self::OS_OUTPUT_FORMAT_WITH_LAT_LON;

    $api_url = sprintf('%s?%s', $api_endpoint, http_build_query($search_query));
    $request = $this->getRequest($api_url);
    $request_w_more_headers = $request->withHeader('User-Agent', $this->userAgent)->withHeader('key', $this->apiKey);

    $query_result = $this->getParsedResponse($request_w_more_headers);
    $json = json_decode($query_result, TRUE);

    if (is_null($json) || !is_array($json)) {
      throw InvalidServerResponse::create($api_url);
    }

    if (empty($json) || empty($json['results'])) {
      return new AddressCollection([]);
    }

    $results = [];
    foreach ($json['results'] as $place) {
      $results[] = OsPlacesAddress::createFromArray([
        'providedBy'       => $this->getName(),
        'streetNumber'     => $place['DPA']['BUILDING_NUMBER'] ?? NULL,
        'streetName'       => $place['DPA']['THOROUGHFARE_NAME'] ?? NULL,
        'flat'             => $place['DPA']['SUB_BUILDING_NAME'] ?? NULL,
        'houseName'        => $place['DPA']['BUILDING_NAME'] ?? NULL,
        'org'              => $place['DPA']['ORGANISATION_NAME'] ?? NULL,
        'locality'         => $place['DPA']['POST_TOWN'] ?? NULL,
        'postalCode'       => $place['DPA']['POSTCODE'] ?? NULL,
        'country'          => 'United Kingdom',
        'countryCode'      => 'GB',
        'display'          => $place['DPA']['ADDRESS'] ?? NULL,
        'formattedAddress' => $place['DPA']['ADDRESS'] ?? NULL,
        'latitude'         => $place['DPA']['LAT'] ?? NULL,
        'longitude'        => $place['DPA']['LNG'] ?? NULL,
        'easting'          => $place['DPA']['X_COORDINATE'] ?? NULL,
        'northing'         => $place['DPA']['Y_COORDINATE'] ?? NULL,
        'uprn'             => $place['DPA']['UPRN'] ?? NULL,
      ]);
    }

    return new AddressCollection($results);
  }

  /**
   * Predicate for spotting UK postcodes.
   */
  public static function isPostcode($query_text) :bool {

    $postcode_regex = sprintf("#%s#i", self::UK_SIMPLE_POSTCODE_REGEX);
    return (bool) preg_match($postcode_regex, $query_text);
  }

  /**
   * Reverse Geocoding is yet to be supported.
   *
   * @todo Implement it.
   *
   * @throws Geocoder\Exception\UnsupportedOperation
   */
  public function reverseQuery(ReverseQuery $query) :AddressCollectionInterface {

    throw new UnsupportedOperation('The OsPlacesGeocoder provider does not support reverse geocoding yet.');
  }

  /**
   * Default output format.
   *
   * The 'EPSG:4326' output format carries latitute and longitude values.  Other
   * possible values are 'BNG', 'EPSG:27700', 'WGS84', 'EPSG:3857', and
   * 'EPSG:4258'.
   */
  const OS_OUTPUT_FORMAT_WITH_LAT_LON = 'EPSG:4326';

  /**
   * Regex for validating *most* UK postcodes.
   *
   * @see https://en.wikipedia.org/wiki/Postcodes_in_the_United_Kingdom#Validation
   */
  const UK_SIMPLE_POSTCODE_REGEX = '^[A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}$';

  /**
   * Query by street address.
   *
   * @var string
   */
  protected $genericAddressQueryUrl;

  /**
   * Query by postcode.
   *
   * @var string
   */
  protected $postcodeQueryUrl;

  /**
   * API key for OS Places API.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * Optional identifier.
   *
   * @var string
   */
  protected $userAgent;

}
