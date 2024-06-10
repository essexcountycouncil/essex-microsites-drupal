<?php

namespace Drupal\group_permissions\Access;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\flexible_permissions\CalculatedPermissions;
use Drupal\flexible_permissions\ChainPermissionCalculator;

/**
 * Collects group permissions for an account.
 */
class GroupPermissionsChainPermissionCalculator extends ChainPermissionCalculator {

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, $scope) {
    $persistent_cache_contexts = $this->getPersistentCacheContexts($scope);
    $initial_cacheability = (new CacheableMetadata())->addCacheContexts($persistent_cache_contexts);
    $cache_keys = ['flexible_permissions', $scope];

    // Whether to switch the user account during cache storage and retrieval.
    //
    // This is necessary because permissions may be stored varying by the user
    // cache context or one of its child contexts. Because we may be calculating
    // permissions for an account other than the current user, we need to ensure
    // that the cache ID for said entry is set according to the passed in
    // account's data.
    //
    // Drupal core does not help us here because there is no way to reuse the
    // cache context logic outside of the caching layer. This means that in
    // order to generate a cache ID based on, let's say, one's permissions, we'd
    // have to copy all of the permission hash generation logic. Same goes for
    // the optimizing/folding of cache contexts.
    //
    // Instead of doing so, we simply set the current user to the passed in
    // account, calculate the cache ID and then immediately switch back. It's
    // the cleanest solution we could come up with that doesn't involve copying
    // half of core's caching layer and that still allows us to use the
    // VariationCache for accounts other than the current user.
    $switch_account = FALSE;
    foreach ($persistent_cache_contexts as $cache_context) {
      [$cache_context_root] = explode('.', $cache_context, 2);
      if ($cache_context_root === 'user') {
        $switch_account = TRUE;
        $this->accountSwitcher->switchTo($account);
        break;
      }
    }

    // Retrieve the permissions from the static cache if available.
    $static_cache_hit = FALSE;
    $persistent_cache_hit = FALSE;
    if ($static_cache = $this->static->get($cache_keys, $initial_cacheability)) {
      $static_cache_hit = TRUE;
      $calculated_permissions = $static_cache->data;
    }
    // Retrieve the permissions from the persistent cache if available.
    elseif ($cache = $this->cache->get($cache_keys, $initial_cacheability)) {
      $persistent_cache_hit = TRUE;
      $calculated_permissions = $cache->data;
    }
    // Otherwise build the permissions and store them in the persistent cache.
    else {
      $calculated_permissions = new GroupPermissionsRefinableCalculatedPermissions();
      foreach ($this->getCalculators() as $calculator) {
        // @see https://www.drupal.org/project/group_permissions/issues/3270219.
        $overwrite = $calculator instanceof IndividualGroupPermissionCalculator || $calculator instanceof SynchronizedGroupPermissionCalculator;
        $calculated_permissions = $calculated_permissions->merge($calculator->calculatePermissions($account, $scope), $overwrite);
      }

      // Apply a cache tag to easily flush the calculated permissions.
      $calculated_permissions->addCacheTags(['flexible_permissions']);
    }

    if (!$static_cache_hit) {
      $cacheability = CacheableMetadata::createFromObject($calculated_permissions);

      // First store the actual calculated permissions in the persistent cache,
      // along with the final cache contexts after all calculations have run.
      if (!$persistent_cache_hit) {
        $this->cache->set($cache_keys, $calculated_permissions, $cacheability, $initial_cacheability);
      }

      // Then convert the calculated permissions to an immutable value object
      // and store it in the static cache so that we don't have to do the same
      // conversion every time we call for the calculated permissions from a
      // warm static cache.
      $calculated_permissions = new CalculatedPermissions($calculated_permissions);
      $this->static->set($cache_keys, $calculated_permissions, $cacheability, $initial_cacheability);
    }

    if ($switch_account) {
      $this->accountSwitcher->switchBack();
    }

    // Return the permissions as an immutable value object.
    return $calculated_permissions;
  }

}
