<?php

namespace Drupal\group_sites\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface;

/**
 * Interface for a Group Sites access policy when no site was detected.
 */
interface GroupSitesNoSiteAccessPolicyInterface extends GroupSitesAccessPolicyInterface {

  /**
   * Applies the policy by altering the calculated permissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to alter the permissions.
   * @param string $scope
   *   The scope to alter the permissions for.
   * @param \Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface $calculated_permissions
   *   The completely built calculated permissions.
   */
  public function alterPermissions(AccountInterface $account, string $scope, RefinableCalculatedPermissionsInterface $calculated_permissions);

}
