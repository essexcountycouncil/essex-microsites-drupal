<?php

namespace Drupal\group_sites;

use Drupal\group_sites\Access\GroupSitesNoSiteAccessPolicyInterface;
use Drupal\group_sites\Access\GroupSitesSiteAccessPolicyInterface;

/**
 * Gathers all group sites access policies and tracks the active one.
 */
class GroupSitesAccessPolicyRepository implements GroupSitesAccessPolicyRepositoryInterface {

  /**
   * The service IDs of access policies for when no site is detected.
   *
   * @var \Drupal\group_sites\Access\GroupSitesNoSiteAccessPolicyInterface[]
   */
  protected array $noSiteAccessPolicies = [];

  /**
   * The service IDs of access policies for when a site is detected.
   *
   * @var \Drupal\group_sites\Access\GroupSitesSiteAccessPolicyInterface[]
   */
  protected array $siteAccessPolicies = [];

  /**
   * {@inheritdoc}
   */
  public function addNoSiteAccessPolicy(GroupSitesNoSiteAccessPolicyInterface $access_policy, string $id): void {
    $this->noSiteAccessPolicies[$id] = $access_policy;
  }

  /**
   * {@inheritdoc}
   */
  public function getNoSiteAccessPolicies(): array {
    return $this->noSiteAccessPolicies;
  }

  /**
   * {@inheritdoc}
   */
  public function addSiteAccessPolicy(GroupSitesSiteAccessPolicyInterface $access_policy, string $id): void {
    $this->siteAccessPolicies[$id] = $access_policy;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteAccessPolicies(): array {
    return $this->siteAccessPolicies;
  }

}
