<?php

namespace Drupal\group_sites\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Interface for a Group Sites access policy when a site was detected.
 */
interface GroupSitesSiteAccessPolicyInterface extends GroupSitesAccessPolicyInterface {

  /**
   * Applies the policy by altering the calculated permissions.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The Group entity representing the detected site.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to alter the permissions.
   * @param string $scope
   *   The scope to alter the permissions for.
   * @param \Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface $calculated_permissions
   *   The completely built calculated permissions.
   */
  public function alterPermissions(GroupInterface $group, AccountInterface $account, string $scope, RefinableCalculatedPermissionsInterface $calculated_permissions);

}
