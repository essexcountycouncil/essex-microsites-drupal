<?php

namespace Drupal\localgov_microsites_group;

use Drupal\Core\Entity\EntityInterface;
use Drupal\domain\DomainInterface;

/**
 * Find Group for Domain.
 *
 * @deprecated in localgov_microsites_group:4.0.0-alpha1 and is removed from
 * localgov_microsites_group:5.0.0.
 * Use \Drupal\group_context_domain\Context\GroupFromDomainContext.
 * @see https://www.drupal.org/project/group_sites/issues/3402181
 */
interface DomainGroupResolverInterface {

  /**
   * Get Group ID for active domain if there is one.
   *
   * @return int|null
   *   Group ID, or NULL if there is no active domain group.
   */
  public function getActiveDomainGroupId(): ?int;

  /**
   * Get domains for an entity.
   *
   * Sadly for new entities group does not add anything. If you know it's on a
   * group entity create form also check the route.
   *
   * @param \Drupal\node\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\domain\DomainInterface[]
   *   The list of domains.
   */
  public function getEntityGroupDomains(EntityInterface $entity): array;

  /**
   * Get group domain for the current route.
   *
   * Tests the route, not the domain, for group context and loads any related
   * domain.
   * Use DomainNegotiatorInterface::getActiveDomain for current domain.
   * Use this::getEntityGroupDomains for domains for groups of an existing
   * entity.
   *
   * @return \Drupal\domain\DomainInterface|null
   *   The list of domains.
   */
  public function getCurrentRouteGroupDomain(): ?DomainInterface;

}
