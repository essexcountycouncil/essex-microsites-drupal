<?php

namespace Drupal\group_sites\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group_sites\GroupSitesAdminModeInterface;

/**
 * Defines a cache context for caching based on admin mode status.
 *
 * Cache context ID: 'user.in_group_sites_admin_mode'.
 */
class GroupSitesAdminModeCacheContext implements CacheContextInterface {

  public function __construct(
    protected GroupSitesAdminModeInterface $adminMode,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return new TranslatableMarkup('User is in group sites admin mode');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->adminMode->isActive() ? 'active' : 'inactive';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $cacheable_metadata = new CacheableMetadata();

    // This cache tag is what needs to be added if someone ever decides to
    // override this service to remove the zero max age set below. It's added
    // here as a precaution as leaving it out would lead to security issues in
    // case the max age below is removed.
    $cacheable_metadata->setCacheTags(['group_sites:admin_mode:' . $this->currentUser->id()]);

    // If this cache context gets folded, it's going to end up as "user".
    // Because we know that this cache context is used during expensive
    // permission calculations, we'd rather each user gets two cache entries
    // instead of constantly having to recalculate their permissions when they
    // briefly toggle admin mode on and off again.
    //
    // If they don't have access to admin mode, then this will always return
    // "inactive" and therefore the user will still only get one cache entry.
    //
    // On top of that, it's a security measure to not allow this cache context
    // to be folded in case someone uses the override in the admin mode, then
    // calculates someone's permissions and forgets to turn the override off or
    // the request crashes before the override is turned back off. In that case,
    // the permissions are still cached for the user as if they were an admin!
    //
    // The only downside is that we now add the performance hit of checking the
    // private temp store on every request, rather than just the user ID.
    return $cacheable_metadata->setCacheMaxAge(0);
  }

}
