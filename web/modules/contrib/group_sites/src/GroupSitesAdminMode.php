<?php

namespace Drupal\group_sites;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Keeps track of whether a user is in admin mode.
 */
class GroupSitesAdminMode implements GroupSitesAdminModeInterface {

  /**
   * The admin mode override flag.
   *
   * @var bool
   */
  protected bool $adminModeOverride = FALSE;

  public function __construct(
    protected PrivateTempStoreFactory $privateTempStoreFactory,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function isActive(): bool {
    if ($this->adminModeOverride) {
      return TRUE;
    }

    $admin_mode = $this->getAdminMode();

    // You always need to have the permission to use admin mode. If you are in
    // admin mode and no longer have the permission, this will kick you out.
    if (!$this->currentUser->hasPermission('use group_sites admin mode')) {
      if ($admin_mode) {
        $this->setAdminMode(FALSE);
      }
      return FALSE;
    }

    return $admin_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function setAdminMode(bool $enabled): void {
    // Always allow to turn off, but never on if lacking the right permission.
    if (!$enabled || $this->currentUser->hasPermission('use group_sites admin mode')) {
      $this->privateTempStoreFactory->get('group_sites')->set('admin_mode', $enabled);
      Cache::invalidateTags(['group_sites:admin_mode:' . $this->currentUser->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setAdminModeOverride(bool $enabled): void {
    $this->adminModeOverride = $enabled;
    Cache::invalidateTags(['group_sites:admin_mode:' . $this->currentUser->id()]);
  }

  /**
   * Gets the admin mode status for the current user.
   *
   * @return bool
   *   The admin mode status.
   */
  protected function getAdminMode(): bool {
    return $this->privateTempStoreFactory->get('group_sites')->get('admin_mode') ?? FALSE;
  }

}
