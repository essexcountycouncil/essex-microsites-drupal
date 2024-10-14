<?php

declare(strict_types = 1);

namespace Drupal\Tests\facets_form_live_total\FunctionalJavascript;

use Drupal\facets\Entity\Facet;
use Drupal\facets_summary\Entity\FacetsSummary;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\facets\Functional\BlockTestTrait;
use Drupal\Tests\facets\Functional\ExampleContentTrait;

/**
 * Tests the Facets Form Live Total functionality.
 *
 * @group facets_form
 */
class FacetsFormLiveTotalTest extends WebDriverTestBase {

  use ExampleContentTrait;
  use BlockTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'facets_form_live_total',
    'facets_search_api_dependency',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->setUpExampleStructure();
    $this->insertExampleContent();
    $this->assertSame(5, $this->indexItems('database_search_index'));
  }

  /**
   * Tests facets summary total refresh.
   */
  public function testTotalRefresh(): void {
    $this->createFacet('Emu', 'emu', 'type', 'page_1', 'views_page__search_api_test_view', FALSE);
    $facet = Facet::load('emu');
    $facet->setOnlyVisibleWhenFacetSourceIsVisible(FALSE);
    $facet->setWidget('facets_form_checkbox');
    $facet->save();

    FacetsSummary::create([
      'name' => 'Owl',
      'id' => 'owl',
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
      'facets' => [
        'emu' => [
          'checked' => TRUE,
          'show_count' => FALSE,
        ],
      ],
      'processor_configs' => [
        'show_count' => [
          'processor_id' => 'show_count',
        ],
      ],
    ])->save();

    $this->placeBlock('facets_summary_block:owl');
    $this->placeBlock('facets_form:search_api:views_page__search_api_test_view__page_1', [
      'live_total' => TRUE,
      'weight' => 10,
    ]);

    $this->drupalGet('search-api-test-fulltext');

    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    $assert->pageTextContains('5 results found');

    $page->checkField('article');
    $this->assertTrue($assert->waitForText('2 results found'));

    $page->uncheckField('article');
    $this->assertTrue($assert->waitForText('5 results found'));
  }

}
