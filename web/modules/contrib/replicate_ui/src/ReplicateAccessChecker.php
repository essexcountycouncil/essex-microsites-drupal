<?php

namespace Drupal\replicate_ui;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Entity\EntityAccessCheck;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Access\PermissionAccessCheck;
use Symfony\Component\Routing\Route;

/**
 * Access checker which checks entity create/view access as well a permission.
 */
class ReplicateAccessChecker implements AccessInterface {

  /**
   * @var \Drupal\user\Access\PermissionAccessCheck
   */
  protected $permAccessChecker;

  /**
   * @var \Drupal\Core\Entity\EntityAccessCheck
   */
  protected $entityAccessChecker;

  /**
   * Creates a new ReplicateAccessChecker instance.
   *
   * @param \Drupal\user\Access\PermissionAccessCheck $permAccessChecker
   * @param \Drupal\Core\Entity\EntityAccessCheck $entityAccessChecker
   */
  public function __construct(PermissionAccessCheck $permAccessChecker, EntityAccessCheck $entityAccessChecker) {
    $this->permAccessChecker = $permAccessChecker;
    $this->entityAccessChecker = $entityAccessChecker;
  }

  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $create_fake_route = clone $route;
    $create_fake_route->setRequirement('_entity_access', $route->getDefault('entity_type_id') . '.create');
    $view_fake_route = clone $route;
    $view_fake_route->setRequirement('_entity_access', $route->getDefault('entity_type_id') . '.create');
    $permission_fake_route = clone $route;
    $permission_fake_route->setRequirements(['_permission' => 'replicate entities']);
    return $this->entityAccessChecker->access($view_fake_route, $route_match, $account)
      ->andIf($this->entityAccessChecker->access($create_fake_route, $route_match, $account))
      ->andIf($this->permAccessChecker->access($permission_fake_route, $account));
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
  }


}
