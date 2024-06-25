<?php

namespace Drupal\group_sites;

/**
 * Defines the admin mode service interface.
 */
interface GroupSitesAdminModeInterface {

  /**
   * Checks if admin mode is active for the current user.
   *
   * @return bool
   *   Whether admin mode is active for the current user.
   */
  public function isActive(): bool;

  /**
   * Sets the admin mode status for the current user.
   *
   * @param bool $enabled
   *   Whether admin mode should be enabled.
   */
  public function setAdminMode(bool $enabled): void;

  /**
   * Forces the admin mode status for the current user.
   *
   * While the override is on, isActive() should always return TRUE, regardless
   * of inner logic such as permission checks.
   *
   * @param bool $enabled
   *   Whether admin mode should be forcibly enabled.
   */
  public function setAdminModeOverride(bool $enabled): void;

}
