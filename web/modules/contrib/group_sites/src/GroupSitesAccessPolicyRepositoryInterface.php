<?php

namespace Drupal\group_sites;

use Drupal\group_sites\Access\GroupSitesNoSiteAccessPolicyInterface;
use Drupal\group_sites\Access\GroupSitesSiteAccessPolicyInterface;

/**
 * Interface for a Group Sites access policy repository.
 */
interface GroupSitesAccessPolicyRepositoryInterface {

  /**
   * Adds an access policy for when no group is determined from context.
   *
   * @param \Drupal\group_sites\Access\GroupSitesNoSiteAccessPolicyInterface $access_policy
   *   The access policy.
   * @param string $id
   *   The service ID.
   */
  public function addNoSiteAccessPolicy(GroupSitesNoSiteAccessPolicyInterface $access_policy, string $id): void;

  /**
   * Returns all defined no site access policies.
   *
   * @return \Drupal\group_sites\Access\GroupSitesNoSiteAccessPolicyInterface[]
   *   The access policies, keyed by service ID.
   */
  public function getNoSiteAccessPolicies(): array;

  /**
   * Adds an access policy for determining site access based on a group.
   *
   * @param \Drupal\group_sites\Access\GroupSitesSiteAccessPolicyInterface $access_policy
   *   The access policy.
   * @param string $id
   *   The service ID.
   */
  public function addSiteAccessPolicy(GroupSitesSiteAccessPolicyInterface $access_policy, string $id): void;

  /**
   * Returns all defined site access policies.
   *
   * @return \Drupal\group_sites\Access\GroupSitesSiteAccessPolicyInterface[]
   *   The access policies, keyed by service ID.
   */
  public function getSiteAccessPolicies(): array;

}
