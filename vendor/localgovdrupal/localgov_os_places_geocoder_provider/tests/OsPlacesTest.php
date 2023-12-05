<?php

declare (strict_types = 1);

namespace LocalgovDrupal\OsPlacesGeocoder\Tests;

use LocalgovDrupal\OsPlacesGeocoder\Provider\OsPlacesGeocoder;
use Geocoder\Collection as AddressCollectionInterface;
use Geocoder\IntegrationTest\BaseTestCase;
use Geocoder\Query\GeocodeQuery;

/**
 * Tests for the OS Places Geocoder.
 *
 * Uses cached query result to test functionality.  This provides a more
 * realistic test experience.
 *
 * Cached responses are saved inside the './cached-query-responses/' directory
 * the first time this test is run.  A **real API key** is necessary on the very
 * first run of this test.  It can be provided through the OS_PLACES_API_KEY
 * environment variable.  This allows the test to fetch a real response from
 * the API endpoint and save it inside the ./cached-query-responses
 * directory.  Subsequent test runs use the saved responses instead of making
 * any real HTTP requests.
 */
class OsPlacesTest extends BaseTestCase {

  /**
   * Test for OsPlacesGeocoder::geocodeQuery().
   *
   * Searches for BN1 3EJ which is a postcode.
   */
  public function testGeocodeQueryForPostcode() {

    $provider = $this->createProvider();

    $result = $provider->geocodeQuery(GeocodeQuery::create('BN1 1JE'));
    $this->assertInstanceOf(AddressCollectionInterface::class, $result);

    $address = $result->first()->toArray();
    $this->assertEquals('22062038', $address['uprn']);
    $this->assertEquals(531044, $address['easting']);
  }

  /**
   * More Test for OsPlacesGeocoder::geocodeQuery().
   *
   * Searches for "Dyke road, Brighton" which is a street address.
   */
  public function testGeocodeQueryForStreetAddress() {

    $provider = $this->createProvider();

    $result = $provider->geocodeQuery(GeocodeQuery::create('Dyke road, Brighton'));
    $this->assertInstanceOf(AddressCollectionInterface::class, $result);

    $address = $result->first()->toArray();
    $this->assertEquals('22047674', $address['uprn']);
    $this->assertEquals(530742, $address['easting']);
    $this->assertEquals(50.824172, $address['latitude']);
    $this->assertEquals('11, DYKE ROAD, BRIGHTON, BN1 3FE', $address['display']);
  }

  /**
   * Prepares the test target.
   */
  protected function createProvider() {

    $provider = new OsPlacesGeocoder($this->getHttpClient(), $this->genericApiUrl, $this->postcodeApiUrl, $this->getApiKey());
    return $provider;
  }

  /**
   * Location of cached responses.
   *
   * This directory is filled in during the initial test run as long as a real
   * API key has been provided.
   */
  protected function getCacheDir() {

    return __DIR__ . '/cached-query-responses';
  }

  /**
   * OS Places API key.
   *
   * When a valid API key for the OS Places API is provided through the
   * OS_PLACES_API_KEY environment variable, responses will be captured and
   * saved inside the ./cached-query-responses directory.  These responses can
   * be used in subsequent test runs without making any actual HTTP requests to
   * the API endpoint.
   */
  protected function getApiKey() {

    return getenv('OS_PLACES_API_KEY') ?: '';
  }

  /**
   * Street address query API endpoint.
   *
   * @var string
   */
  protected $genericApiUrl = 'https://api.os.uk/search/places/v1/find';

  /**
   * Postcode query API endpoint.
   *
   * @var string
   */
  protected $postcodeApiUrl = 'https://api.os.uk/search/places/v1/postcode';

}
