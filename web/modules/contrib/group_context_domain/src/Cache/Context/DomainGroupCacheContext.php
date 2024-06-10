<?php

namespace Drupal\group_context_domain\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\group_context_domain\GroupFromDomainContextTrait;

/**
 * Defines a cache context for "per group from domain" caching.
 *
 * Cache context ID: 'url.site.group'.
 */
class DomainGroupCacheContext implements CacheContextInterface {

  use GroupFromDomainContextTrait;

  public function __construct(
    protected DomainNegotiatorInterface $domainNegotiator,
    protected EntityRepositoryInterface $entityRepository,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Group from domain');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    if ($group = $this->getGroupFromDomain()) {
      return $group->id();
    }

    // If no group was found on the route, we return a string that cannot be
    // mistaken for a group ID or group type.
    return 'group.none';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $cacheable_metadata = new CacheableMetadata();

    // The group is saved on the domain, so the domain record needs to be added.
    if ($domain = $this->domainNegotiator->getActiveDomain()) {
      $cacheable_metadata->addCacheableDependency($domain);
    }

    return $cacheable_metadata;
  }

}
