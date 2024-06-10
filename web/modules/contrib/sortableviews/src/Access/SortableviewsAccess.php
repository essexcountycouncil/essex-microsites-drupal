<?php

namespace Drupal\sortableviews\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determines whether user has access to edit a views base entity.
 */
class SortableviewsAccess implements AccessInterface {

  /**
   * An instance of the entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * An instance of ViewExecutableFactory.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $viewsExecutableFactory;

  /**
   * Builds a new SortableViewsAccess object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\views\ViewExecutableFactory $views_executable_factory
   *   An instantiated ViewExecutableFactory object.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, ViewExecutableFactory $views_executable_factory) {
    $this->entityManager = $entity_manager;
    $this->viewsExecutableFactory = $views_executable_factory;
  }

  /**
   * Checks logged in user has access to ajax path.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Request $request, AccountInterface $account) {
    // Check request.
    $view_name = $request->get('view_name');
    $current_order = $request->get('current_order');
    $display_name = $request->get('display_name');
    if (!$view_name || !$current_order || !$display_name) {
      return AccessResult::forbidden()->setReason('Path was not called with appropiate parameters.')->setCacheMaxAge(0);
    }

    // Load the view.
    $view_entity = $this->entityManager->getStorage('view')->load($view_name);
    if (!$view_entity) {
      return AccessResult::forbidden()->setReason('Could not load specified view.')->setCacheMaxAge(0);
    }

    // Obtain the base entity type.
    $view = $this->viewsExecutableFactory->get($view_entity);
    $base_entity_type = $view->getBaseEntityType();
    if (!$base_entity_type) {
      return AccessResult::forbidden()->setReason('The view refers to an entity type that no longer exists.')->setCacheMaxAge(0);
    }

    // Load Display settings and verify the field is set.
    $view->setDisplay($display_name);
    $field = $view->getStyle()->options['weight_field'];
    if (!$field) {
      return AccessResult::forbidden()->setReason('The weight field was not specified in the view.')->setCacheMaxAge(0);
    }

    // Load all entities in $current_order.
    $entities = $this->entityManager->getStorage($base_entity_type->id())->loadMultiple(array_values($current_order));
    if (count($entities) != count($current_order)) {
      return AccessResult::forbidden()->setReason('Not all entities appear to belong to the same entity type.')->setCacheMaxAge(0);
    }

    // Check access for each entity (Access may change per bundle).
    foreach ($entities as $entity) {
      if (!$entity->access('update', $account) || !$entity->get($field)->access('update', $account)) {
        return AccessResult::forbidden()->setReason('User is unable to edit entity ' . $entity->id() . ' of type ' . $base_entity_type->id())->setCacheMaxAge(0);
      }
    }

    // Save the entity type and field in the request.
    $request->attributes->set('entity_type', $base_entity_type->id());
    $request->attributes->set('weight_field', $field);

    // Good to go.
    return AccessResult::allowed()->setCacheMaxAge(0);
  }

}
