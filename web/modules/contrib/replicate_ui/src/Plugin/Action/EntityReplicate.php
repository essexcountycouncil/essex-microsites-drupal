<?php

namespace Drupal\replicate_ui\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Redirects to a node deletion form.
 *
 * @Action(
 *   id = "entity_replicate",
 *   deriver = "\Drupal\replicate_ui\Plugin\Derivative\EntityReplicateActions",
 * )
 */
class EntityReplicate extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\replicate_ui\ReplicateAccessChecker $access_check */
    $access_check = \Drupal::service('replicate_ui.access_check');
    $entity_type_id = $this->getPluginDefinition()['type'];

    $route = new Route(
      $entity_type_id,
      [
        '_entity_form' => "$entity_type_id.replicate",
        'entity_type_id' => $entity_type_id,
      ],
      [
        '_replicate_access' => 'TRUE',
      ],
      [
        'parameters' => [
          $entity_type_id => [
            'type' => 'entity:' . $entity_type_id,
          ],
        ],
      ]
    );
    $route_match = new RouteMatch("entity.$entity_type_id.replicate", $route, [$entity_type_id => $object], [$entity_type_id => $object->id()]);

    /** @var \Drupal\Core\Access\AccessResultInterface $result */
    $result = $access_check->access($route, $route_match, $account);
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple([$object]);
  }

}
