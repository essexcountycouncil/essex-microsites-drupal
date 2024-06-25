<?php

namespace Drupal\group_sites\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface;

/**
 * Access policy that denies all access when there is no site.
 */
class DenyAllNoSiteAccessPolicy implements GroupSitesNoSiteAccessPolicyInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->t('Deny all Group access');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->t('Plays it safe, does not allow any group entity to hand out permissions.');
  }

  /**
   * {@inheritdoc}
   */
  public function alterPermissions(AccountInterface $account, string $scope, RefinableCalculatedPermissionsInterface $calculated_permissions) {
    $calculated_permissions->removeItems();
  }

}
