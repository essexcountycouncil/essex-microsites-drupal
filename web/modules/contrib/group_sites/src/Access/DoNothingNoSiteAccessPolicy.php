<?php

namespace Drupal\group_sites\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface;

/**
 * Access policy that does nothing when there is no site.
 */
class DoNothingNoSiteAccessPolicy implements GroupSitesNoSiteAccessPolicyInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->t('Do nothing');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->t('Do not grant or revoke any permissions at all. The site will function as if Group Sites is not even installed.');
  }

  /**
   * {@inheritdoc}
   */
  public function alterPermissions(AccountInterface $account, string $scope, RefinableCalculatedPermissionsInterface $calculated_permissions) {}

}
