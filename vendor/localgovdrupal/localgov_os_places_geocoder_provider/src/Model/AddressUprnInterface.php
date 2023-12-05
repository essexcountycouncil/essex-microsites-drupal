<?php

declare(strict_types = 1);

namespace LocalgovDrupal\OsPlacesGeocoder\Model;

/**
 * Address URPN record.
 */
interface AddressUprnInterface {

  /**
   * Returns the Unique Property Reference Number.
   *
   * @see https://en.wikipedia.org/wiki/Unique_Property_Reference_Number
   */
  public function getUprn() :string;

}
