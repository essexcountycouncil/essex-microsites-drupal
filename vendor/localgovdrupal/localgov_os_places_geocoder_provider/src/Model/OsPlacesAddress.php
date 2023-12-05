<?php

declare(strict_types = 1);

namespace LocalgovDrupal\OsPlacesGeocoder\Model;

use Geocoder\Model\Address;

/**
 * Ordnance Survey places address record.
 *
 * Includes Ordnance Survey Grid reference in addition to everything a
 * UPRN-based address record would have.
 */
final class OsPlacesAddress extends Address implements AddressOsGridRefInterface, AddressUprnInterface {

  /**
   * Unique Property Reference Number.
   *
   * @var string
   *
   * @see https://en.wikipedia.org/wiki/Unique_Property_Reference_Number
   */
  protected $uprn = '';

  /**
   * The full address in one line.
   *
   * @var string
   */
  protected $displayName = '';

  /**
   * Flat number.
   *
   * @var string
   */
  protected $flat = '';

  /**
   * House name.
   *
   * @var string
   */
  protected $houseName = '';

  /**
   * Organisation.
   *
   * @var string
   */
  protected $org = '';


  /**
   * All numeric Ordnance Survey National Grid reference.
   *
   * @var OsGridRef
   *
   * @see https://en.wikipedia.org/wiki/Ordnance_Survey_National_Grid#All-numeric_grid_references
   */
  protected $osGridRef;

  /**
   * Getter for the OS grid reference object.
   */
  public function getOsGridRef() :OsGridRef {

    return $this->osGridRef;
  }

  /**
   * {@inheritdoc}
   */
  public function getUprn() :string {

    return $this->uprn;
  }

  /**
   * The full address, in one line.
   */
  public function getDisplayName() :string {

    return $this->displayName;
  }

  /**
   * Returns the flat number if any.
   */
  public function getFlat() :string {

    return $this->flat;
  }

  /**
   * Returns the house name if any.
   */
  public function getHouseName() :string {

    return $this->houseName;
  }

  /**
   * Returns the organisation name if any.
   */
  public function getOrganisationName() :string {

    return $this->org;
  }

  /**
   * Creates an Address from an array.
   *
   * @return static
   */
  public static function createFromArray(array $data) {

    $self = parent::createFromArray($data);

    if (isset($data['easting']) && isset($data['northing'])) {
      $self->osGridRef = new OsGridRef((int) $data['easting'], (int) $data['northing']);
    }

    $self->uprn        = $data['uprn'] ?? '';
    $self->displayName = $data['display'] ?? '';
    $self->flat        = $data['flat'] ?? '';
    $self->houseName   = $data['houseName'] ?? '';
    $self->org         = $data['org'] ?? '';

    return $self;
  }

  /**
   * Appends osgrid, uprn and display value to Location array.
   *
   * {@inheritdoc}
   */
  public function toArray() :array {

    $array = parent::toArray();

    if ($this->osGridRef) {
      $array['easting']  = $this->osGridRef->getEasting();
      $array['northing'] = $this->osGridRef->getNorthing();
    }

    $array['uprn']      = $this->getUprn();
    $array['display']   = $this->getDisplayName();
    $array['flat']      = $this->getFlat();
    $array['houseName'] = $this->getHouseName();
    $array['org']       = $this->getOrganisationName();

    return $array;
  }

}
