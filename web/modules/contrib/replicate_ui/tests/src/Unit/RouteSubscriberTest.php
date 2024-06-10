<?php

namespace Drupal\Tests\replicate_ui\Unit;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\replicate_ui\RouteSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\replicate_ui\RouteSubscriber
 * @group replicate
 */
class RouteSubscriberTest extends UnitTestCase {

  /**
   * @covers ::onRouteBuild
   */
  public function testDisabledReplicateFunctionality() {
    $em = $this->setupEntityManager();
    $config_manager = $this->getConfigFactoryStub([
      'replicate_ui.settings' => ['entity_types' => []],
    ]);

    $subscriber = new RouteSubscriber($em->reveal(), $config_manager);

    $routes = $this->setupRouteCollection();
    $event = new RouteBuildEvent($routes);

    $this->assertCount(4, $routes);
    $subscriber->onRouteBuild($event);
    $this->assertCount(4, $routes);
  }

  /**
   * @covers ::onRouteBuild
   */
  public function testEnabledReplicateFunctionality() {
    $em = $this->setupEntityManager();
    $config_manager = $this->getConfigFactoryStub([
      'replicate_ui.settings' => ['entity_types' => ['entity_test_1', 'entity_test_2']],
    ]);

    $subscriber = new RouteSubscriber($em->reveal(), $config_manager);

    $routes = $this->setupRouteCollection();
    $event = new RouteBuildEvent($routes);

    $this->assertCount(4, $routes);
    $subscriber->onRouteBuild($event);
    $this->assertCount(6, $routes);

    $this->assertEquals('/entity_test_1/{entity_test_1}/replicate', $routes->get('entity.entity_test_1.replicate')->getPath());
    $this->assertEquals('entity_test_1.replicate', $routes->get('entity.entity_test_1.replicate')->getDefault('_entity_form'));
    $this->assertFalse($routes->get('entity.entity_test_1.replicate')->hasOption('_admin_route'));
    $this->assertEquals('/entity_test_2/{entity_test_2}/replicate', $routes->get('entity.entity_test_2.replicate')->getPath());
    $this->assertEquals('entity_test_2.replicate', $routes->get('entity.entity_test_2.replicate')->getDefault('_entity_form'));
    $this->assertTrue($routes->get('entity.entity_test_2.replicate')->getOption('_admin_route'));
  }

  protected function setupEntityManager() {
    $entity_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_manager->getDefinitions()->willReturn([
      'entity_test_1' => new ContentEntityType([
        'id' => 'entity_test_1',
        'links' => [
          'canonical' => '/entity_test_1/{entity_test_1}',
        ],
      ]),
      'entity_test_2' => new ContentEntityType([
        'id' => 'entity_test_2',
        'links' => [
          'canonical' => '/entity_test_2/{entity_test_2}',
        ],
      ]),
    ]);

    return $entity_manager;
  }

  protected function setupRouteCollection() {
    $route_collection = new RouteCollection();
    $route_collection->add('entity.entity_test_1.canonical', new Route('/entity_test_1/{entity_test_1}'));
    $route_collection->add('entity.entity_test_2.canonical', new Route('/entity_test_2/{entity_test_2}'));
    $route_collection->add('entity.entity_test_1.edit_form', new Route('/entity_test_1/{entity_test_1}/edit'));
    $route_collection->add('entity.entity_test_2.edit_form', new Route('/entity_test_2/{entity_test_2}/edit', [], [], ['_admin_route' => TRUE]));

    return $route_collection;
  }

}
