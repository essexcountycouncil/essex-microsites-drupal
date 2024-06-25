<?php

namespace Drupal\group_content_menu\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes.
 *
 * Access is based on whether a piece of group content belongs to the group that
 * was also specified in the route.
 */
class GroupOwnsMenuContentAccessChecker implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * GroupOwnsMenuContentAccessChecker constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $must_own_content = $route->getRequirement('_group_menu_owns_content') === 'TRUE';

    // Don't interfere if no group or menu was specified.
    $parameters = $route_match->getParameters();
    if (!$parameters->has('group') || !$parameters->has('group_content_menu')) {
      return AccessResult::neutral();
    }

    /** @var \Drupal\group\Entity\GroupRelationshipInterface[] $group_relationships */
    $group_relationships = $this->entityTypeManager->getStorage('group_relationship')->loadByEntity($parameters->get('group_content_menu'));
    $group_relationship = reset($group_relationships);
    if (!$group_relationship) {
      return AccessResult::neutral();
    }

    // Don't interfere if the group isn't a real group.
    $group = $parameters->get('group');
    if (!$group instanceof GroupInterface) {
      return AccessResult::neutral();
    }

    // Don't interfere if the group content isn't a real group relationship entity.
    if (!$group_relationship instanceof GroupRelationshipInterface) {
      return AccessResult::neutral();
    }

    // If we have a group and group content, see if the owner matches.
    $group_owns_content = $group_relationship->getGroupId() === $group->id();

    // Only allow access if the group content is owned by the group and
    // _group_menu_owns_content is set to TRUE or the other way around.
    $result = AccessResult::allowedIf($group_owns_content xor !$must_own_content);
    return $result;
  }

}
