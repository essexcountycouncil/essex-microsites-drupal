<?php

declare(strict_types = 1);

namespace LocalgovDrupal\OsPlacesGeocoder\Model;

/**
 * Ordnance Survey Location record interface.
 */
interface AddressOsGridRefInterface {

  /**
   * Returns the OS grid reference object.
   */
  public function getOsGridRef() :OsGridRef;

}
