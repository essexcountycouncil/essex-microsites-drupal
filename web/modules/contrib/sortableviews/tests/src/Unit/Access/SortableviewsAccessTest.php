<?php

namespace Drupal\Tests\sortableviews\Unit\Access;

use Drupal\sortableviews\Access\SortableviewsAccess;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\sortableviews\Access\SortableviewsAccess
 * @group sortableviews
 */
class SortableviewsAccessTest extends UnitTestCase {

  /**
   * The instance of SortableviewsAccess to be tested.
   *
   * @var \Drupal\sortableviews\Access\SortableviewsAccess
   */
  protected $sortableViewsAccess;

  /**
   * An object mocked from AccountInterface.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $view_style = $this->createMock('Drupal\views\Plugin\views\style\StylePluginBase');
    $view_style->options['weight_field'] = 'some_field';

    $entity_type = $this->createMock('Drupal\Component\Plugin\Definition\PluginDefinitionInterface');
    $entity_type->expects($this->any())
      ->method('id')
      ->willReturn('some_entity');

    $view_executable = $this->createMock('Drupal\views\ViewExecutable');
    $view_executable->expects($this->any())
      ->method('getBaseEntityType')
      ->willReturn($entity_type);
    $view_executable->expects($this->any())
      ->method('getStyle')
      ->willReturn($view_style);

    $view_entity = $this->createMock('Drupal\views\ViewEntityInterface');

    $executable_factory = $this->createMock('Drupal\views\ViewExecutableFactory');
    $executable_factory->expects($this->any())
      ->method('get')
      ->with($view_entity)
      ->willReturn($view_executable);

    $entities = [];
    for ($aux = 1; $aux <= 2; $aux++) {
      $entity = $this->createMock('Drupal\Core\Access\AccessibleInterface');
      $entity->expects($this->any())
        ->method('access')
        ->willReturn(TRUE);
      $entities[] = $entity;
    }

    $entity_storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $entity_storage->expects($this->any())
      ->method('load')
      ->with('some_view')
      ->willReturn($view_entity);
    $entity_storage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn($entities);

    $entity_manager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $entity_manager->expects($this->any())
      ->method('getStorage')
      ->willReturn($entity_storage);

    $this->sortableViewsAccess = new SortableviewsAccess($entity_manager, $executable_factory);
    $this->account = $this->createMock('Drupal\Core\Session\AccountInterface');
  }

  /**
   * Tests the access method.
   *
   * @covers ::access
   *
   * @dataProvider dataProvider
   */
  public function testAccess(Request $request, $is_valid) {
    $result = $this->sortableViewsAccess->access($request, $this->account);
    if ($is_valid) {
      $this->assertTrue($result instanceof AccessResultAllowed);
      $this->assertEquals($request->get('entity_type'), 'some_entity');
      $this->assertEquals($request->get('weight_field'), 'some_field');
    }
    else {
      $this->assertTrue($result instanceof AccessResultForbidden);
    }
  }

  /**
   * Provides test data for testAccess().
   */
  public function dataProvider() {
    $data = [];

    $request = new Request();
    $data[] = [$request, FALSE];
    unset($request);

    $request = new Request();
    $request->attributes->set('view_name', 'some_view');
    $request->attributes->set('display_name', uniqid());
    $request->attributes->set('current_order', [1, 2]);
    $data[] = [$request, TRUE];
    unset($request);

    return $data;
  }

}
