<?php

namespace Drupal\group_sites;

/**
 * An exception thrown for malformed group sites access policies.
 */
class GroupSitesAccessPolicyException extends \RuntimeException {

  public function __construct(string $service_id, string $interface) {
    $message = sprintf('Service "%s" does not implement %s.', $service_id, $interface);
    parent::__construct($message);
  }

}
