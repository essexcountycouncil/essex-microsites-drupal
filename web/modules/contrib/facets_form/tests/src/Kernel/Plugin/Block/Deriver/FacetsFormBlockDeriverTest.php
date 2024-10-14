<?php

declare(strict_types = 1);

namespace Drupal\Tests\facets_form\Kernel\Plugin\Block\Deriver;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * @coversDefaultClass \Drupal\facets_form\Plugin\Block\Deriver\FacetsFormBlockDeriver
 * @group facets_form
 */
class FacetsFormBlockDeriverTest extends EntityKernelTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'facets',
    'facets_form',
    'rest',
    'search_api',
    'search_api_db',
    'search_api_test_db',
    'search_api_test_example_content',
    'search_api_test_views',
    'serialization',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('facets_facet');
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');

    $this->container->get('state')
      ->set('search_api_use_tracking_batch', FALSE);

    // Set tracking page size so tracking will work properly.
    $this->config('search_api.settings')
      ->set('tracking_page_size', 100)
      ->save();

    $this->installConfig([
      'search_api_test_example_content',
      'search_api_test_db',
      'facets_form',
    ]);

    $this->installConfig('search_api_test_views');
  }

  /**
   * Tests that we have a block derivative for available facet sources.
   */
  public function testBlockDerivatives(): void {
    $facet_sources = $this->container
      ->get('plugin.manager.facets.facet_source')
      ->getDefinitions();
    $this->assertCount(10, $facet_sources);

    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = $this->container->get('plugin.manager.block');

    foreach ($facet_sources as $id => $source) {
      $this->assertTrue($block_manager->hasDefinition("facets_form:{$id}"));
    }
  }

}
