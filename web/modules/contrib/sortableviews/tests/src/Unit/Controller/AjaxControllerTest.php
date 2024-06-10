<?php

namespace Drupal\Tests\sortableviews\Unit\Controller;

use Drupal\sortableviews\Controller\AjaxController;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\sortableviews\Controller\AjaxController
 * @group sortableviews
 */
class AjaxControllerTest extends UnitTestCase {

  /**
   * The instance of AjaxController to be tested.
   *
   * @var \Drupal\sortableviews\Controller\AjaxController
   */
  protected $ajaxController;

  /**
   * Instance of \ReflectionMethod for testing of ::retrieveOrderFromRequest().
   *
   * @var \ReflectionMethod
   */
  protected $method;

  /**
   * Initializes container prior to test execution.
   */
  private function initializeContainer() {
    $container = new ContainerBuilder();

    // Mock the translation service.
    $translation_service = $this->createMock('Drupal\Core\StringTranslation\TranslationInterface');
    $container->set('string_translation', $translation_service);

    // Mock the renderer service.
    $renderer = $this->createMock('Drupal\Core\Render\RendererInterface');
    $render = 'something';
    $renderer->expects($this->any())
      ->method('renderRoot')
      ->with()
      ->willReturnCallback(function (&$elements) use ($render) {
        $elements['#attached'] = [];
        return $render;
      });
    $container->set('renderer', $renderer);

    // Create the container.
    \Drupal::setContainer($container);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->initializeContainer();

    $entity_storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $entity_storage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn([]);

    $entity_manager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $entity_manager->expects($this->any())
      ->method('getStorage')
      ->with('some_type')
      ->willReturn($entity_storage);

    $this->ajaxController = new AjaxController($entity_manager);
    $class = new \ReflectionClass($this->ajaxController);
    $this->method = $class->getMethod('retrieveOrderFromRequest');
    $this->method->setAccessible(TRUE);
  }

  /**
   * Tests the ajaxSave method.
   *
   * @covers ::ajaxSave
   */
  public function testAjaxSave() {
    $dom_id = 'domid';

    $request = new Request();
    $request->attributes->set('entity_type', 'some_type');
    $request->attributes->set('weight_field', 'some_field');
    $request->attributes->set('items_per_page', 2);
    $request->attributes->set('sort_order', 'asc');
    $request->attributes->set('page_number', 0);
    $request->attributes->set('dom_id', $dom_id);
    $request->attributes->set('current_order', []);
    $response = $this->ajaxController->ajaxSave($request);

    $this->assertTrue($response instanceof AjaxResponse);

    $commands = $response->getCommands($response);

    $this->assertEquals('message', $commands[0]['command']);
    $this->assertEquals('Changes have been saved.', $commands[0]['message']->getUntranslatedString());

    $this->assertEquals('remove', $commands[1]['command']);
    $this->assertEquals('.js-view-dom-id-' . $dom_id . ' .sortableviews-ajax-trigger', $commands[1]['selector']);
  }

  /**
   * Tests adjusted order for entities when not in pager.
   *
   * @covers ::retrieveOrderFromRequest
   */
  public function testAnyOrderNotInPager() {
    // Test order when not in a pager.
    $request = new Request();
    $page = [2, 4, 6, 8];
    $request->attributes->set('current_order', $page);
    $result = $this->method->invokeArgs($this->ajaxController, [$request]);
    $this->assertEquals($page, $result);
  }

  /**
   * Tests adjusted order for entities when in ASC pager.
   *
   * Uses an imaginary view with 3 pages, 3 items per page
   * and 8 rows.
   *
   * @covers ::retrieveOrderFromRequest
   */
  public function testAscOrderInPager() {
    $request = new Request();
    $request->attributes->set('items_per_page', 3);
    $request->attributes->set('sort_order', 'asc');

    // Test page one.
    $page = [1, 2, 3];
    $request->attributes->set('page_number', 0);
    $request->attributes->set('current_order', $page);
    $result = $this->method->invokeArgs($this->ajaxController, [$request]);
    $this->assertEquals($page, $result);

    // Test page two.
    $page = [5, 6, 4];
    $request->attributes->set('page_number', 1);
    $request->attributes->set('current_order', $page);
    $result = $this->method->invokeArgs($this->ajaxController, [$request]);
    $this->assertEquals([
      3 => 5,
      4 => 6,
      5 => 4,
    ], $result);

    // Test page three.
    $page = [8, 7];
    $request->attributes->set('page_number', 2);
    $request->attributes->set('current_order', $page);
    $result = $this->method->invokeArgs($this->ajaxController, [$request]);
    $this->assertEquals([
      6 => 8,
      7 => 7,
    ], $result);
  }

  /**
   * Tests adjusted order for entities when in DESC pager.
   *
   * Uses an imaginary view with 3 pages, 3 items per page
   * and 7 rows.
   *
   * @covers ::retrieveOrderFromRequest
   */
  public function testDescOrderInPager() {
    $request = new Request();
    $request->attributes->set('items_per_page', 3);
    $request->attributes->set('sort_order', 'desc');
    $request->attributes->set('total_rows', 7);

    // Test page one.
    $page = [6, 7, 5];
    $request->attributes->set('page_number', 0);
    $request->attributes->set('current_order', $page);
    $result = $this->method->invokeArgs($this->ajaxController, [$request]);
    $this->assertEquals([
      4 => 6,
      5 => 7,
      6 => 5,
    ], $result);

    // Test page two.
    $page = [4, 2, 3];
    $request->attributes->set('page_number', 1);
    $request->attributes->set('current_order', $page);
    $result = $this->method->invokeArgs($this->ajaxController, [$request]);
    $this->assertEquals([
      1 => 4,
      2 => 2,
      3 => 3,
    ], $result);

    // Test page three.
    $page = [1];
    $request->attributes->set('page_number', 2);
    $request->attributes->set('current_order', $page);
    $result = $this->method->invokeArgs($this->ajaxController, [$request]);
    $this->assertEquals($page, $result);
  }

}
