<?php

namespace Drupal\domain_group;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\group\Context\GroupRouteContextTrait;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Find Group for Domain.
 */
class DomainGroupResolver implements DomainGroupResolverInterface {

  use GroupRouteContextTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The domain negotiator service.
   *
   * @var \Drupal\Domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * DomainGroupHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type manager.
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   Domain negotatior service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   Current route match service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DomainNegotiatorInterface $domain_negotiator, RouteMatchInterface $current_route_match) {
    $this->entityTypeManager = $entity_type_manager;
    $this->domainNegotiator = $domain_negotiator;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * Get Group ID for active domain if there is one.
   *
   * @return int|null
   *   Group ID, or NULL if there is no active domain group.
   */
  public function getActiveDomainGroupId(): ?int {
    $active = $this->domainNegotiator->getActiveDomain();

    // Not yet configured.
    if (empty($active)) {
      return NULL;
    }
    // If active is default domain.
    if ($active->isDefault()) {
      return NULL;
    }
    $group_id = $active->getThirdPartySetting('domain_group', 'group');
    if (empty($group_id)) {
      return NULL;
    }

    return $group_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityGroupDomains(EntityInterface $entity): array {
    $domains = [];
    $groups = [];

    if ($entity instanceof GroupRelationshipInterface) {
      $group_relationship_array = [$entity];
    }
    elseif ($entity instanceof GroupInterface) {
      $groups = [$entity];
    }
    elseif (!$entity->isNew()) {
      $group_relationship_array = $this->entityTypeManager->getStorage('group_relationship')->loadByEntity($entity);
    }

    if (!empty($group_relationship_array)) {
      foreach ($group_relationship_array as $group_relationship) {
        $groups[] = $group_relationship->getGroup();
      }
    }

    if (!empty($groups)) {
      foreach ($groups as $group) {
        if ($group) {
          $domain = $this->entityTypeManager->getStorage('domain')->load('group_' . $group->id());
          if ($domain) {
            $domains[$domain->id()] = $domain;
          }
        }
      }
    }

    return $domains;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentRouteGroupDomain(): ?DomainInterface {
    $group = $this->getGroupFromRoute();
    if ($group) {
      $domain = $this->entityTypeManager->getStorage('domain')->load('group_' . $group->id());
      if ($domain) {
        return $domain;
      }
    }

    return NULL;
  }

}
