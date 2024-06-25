<?php

namespace Drupal\group_sites\Access;

/**
 * Interface for a Group Sites access policy.
 */
interface GroupSitesAccessPolicyInterface {

  /**
   * Gets a human-readable label for the access policy.
   *
   * @return string
   *   The label.
   */
  public function getLabel(): string;

  /**
   * Gets a description for the access policy.
   *
   * @return string
   *   The description.
   */
  public function getDescription(): string;

}
