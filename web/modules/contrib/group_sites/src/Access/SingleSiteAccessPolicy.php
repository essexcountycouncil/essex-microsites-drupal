<?php

namespace Drupal\group_sites\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flexible_permissions\CalculatedPermissionsItem;
use Drupal\flexible_permissions\ChainPermissionCalculatorInterface;
use Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\group_sites\GroupSitesAdminModeInterface;

/**
 * Access policy that only keeps one site active.
 */
class SingleSiteAccessPolicy implements GroupSitesSiteAccessPolicyInterface {

  use StringTranslationTrait;

  public function __construct(
    protected GroupSitesAdminModeInterface $adminMode,
    protected ChainPermissionCalculatorInterface $chainCalculator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->t('Single Group remains active');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->t('Only the active site can hand out group permissions, all other groups (even from other types) are deactivated.');
  }

  /**
   * {@inheritdoc}
   */
  public function alterPermissions(GroupInterface $group, AccountInterface $account, string $scope, RefinableCalculatedPermissionsInterface $calculated_permissions) {
    if ($scope === PermissionScopeInterface::INDIVIDUAL_ID) {
      // We only keep the item of the active group.
      $individual_item = $calculated_permissions->getItem($scope, $group->id());

      // The member check below varies based on membership of the group.
      $calculated_permissions->addCacheContexts(['user.is_group_member:' . $group->id()]);

      // Temporarily activate admin mode so we can calculate the actual insider
      // or outsider permissions and then flatten those into an individual item.
      $this->adminMode->setAdminModeOverride(TRUE);
      $bundle_permissions_scope = $group->getMember($account) ? PermissionScopeInterface::INSIDER_ID : PermissionScopeInterface::OUTSIDER_ID;
      $bundle_permissions = $this->chainCalculator->calculatePermissions($account, $bundle_permissions_scope);
      $this->adminMode->setAdminModeOverride(FALSE);

      if ($bundle_item = $bundle_permissions->getItem($bundle_permissions_scope, $group->bundle())) {
        if ($individual_item) {
          $permissions = array_merge($bundle_item->getPermissions(), $individual_item->getPermissions());
          $is_admin = $bundle_item->isAdmin() || $individual_item->isAdmin();
        }
        else {
          $permissions = $bundle_item->getPermissions();
          $is_admin = $bundle_item->isAdmin();
        }

        $item = new CalculatedPermissionsItem(
          $scope,
          $group->id(),
          $permissions,
          $is_admin
        );
      }
      else {
        $item = $individual_item;
      }
    }

    // Remove all items, regardless of scope.
    $calculated_permissions->removeItemsByScope($scope);

    // Add back the individual item, along with merged synchronized item.
    if (!empty($item)) {
      $calculated_permissions->addItem($item);
    }
  }

}
