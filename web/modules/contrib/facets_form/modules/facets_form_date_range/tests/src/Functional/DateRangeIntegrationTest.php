<?php

declare(strict_types = 1);

namespace Drupal\Tests\facets_form_date_range\Functional;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\facets\Entity\Facet;
use Drupal\facets_summary\Entity\FacetsSummary;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\facets\Functional\BlockTestTrait;
use Drupal\Tests\facets_form\Traits\FacetUrlTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Test the facets form with date range widget.
 *
 * @group facets_form
 */
class DateRangeIntegrationTest extends BrowserTestBase {

  use BlockTestTrait;
  use ContentTypeCreationTrait;
  use FacetUrlTestTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'facets_summary',
    'facets_form_date_range',
    'facets_form_search_api_dependency',
    'node',
    'search_api_test_db',
    'views',
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

    $this->createContentType(['type' => 'bundle_1', 'name' => 'Bundle 1']);
    $this->createContentType(['type' => 'bundle_2', 'name' => 'Bundle 2']);
    $date = new DrupalDateTime('15-08-2021');
    $this->createNode([
      'title' => 'Llama',
      'type' => 'bundle_1',
      'created' => $date->getTimestamp(),
    ]);
    $date = new DrupalDateTime('17-08-2021');
    $this->createNode([
      'title' => 'Emu',
      'type' => 'bundle_2',
      'created' => $date->getTimestamp(),
    ]);

    /** @var \Drupal\search_api\Entity\Index $index */
    $index = Index::load('test');
    $this->assertSame(2, $index->indexItems());
  }

  /**
   * Test the facets form with date range widget.
   */
  public function testDateRangeDateOnly(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    FacetsSummary::create([
      'name' => 'Facets Summary',
      'id' => 'facets_summary_test',
      'facet_source_id' => 'search_api:views_page__test__page_1',
      'facets' => [
        'authored_on' => [
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
    $this->drupalPlaceBlock('facets_summary_block:facets_summary_test');
    $this->drupalPlaceBlock('facets_form:search_api:views_page__test__page_1');
    $this->drupalGet('test');
    $assert->elementsCount('css', '.views-row', 2);
    $assert->pageTextContains('Llama');
    $assert->pageTextContains('Emu');
    $this->assertFacetsSummary(2, NULL);
    $form = $assert->elementExists('css', 'form#facets-form');
    $assert->elementExists('css', 'input#edit-authored-on-from-date', $form);
    $assert->elementExists('css', 'input#edit-authored-on-to-date', $form);
    // Test greater or equals operator.
    $page->fillField('edit-authored-on-from-date', '2021-08-16');
    $form->pressButton('Search');
    $this->assertCurrentUrl('test', ['f' => ['authored_on:2021-08-16~']]);
    $assert->elementsCount('css', '.views-row', 1);
    $assert->pageTextNotContains('Llama');
    $assert->pageTextContains('Emu');
    $this->assertFacetsSummary(1, '(-) After Mon, 08/16/2021 - 00:00');
    // Test smaller or equals operator.
    $page->fillField('edit-authored-on-from-date', '');
    $page->fillField('edit-authored-on-to-date', '2021-08-16');
    $form->pressButton('Search');
    $this->assertCurrentUrl('test', ['f' => ['authored_on:~2021-08-16']]);
    $assert->elementsCount('css', '.views-row', 1);
    $assert->pageTextContains('Llama');
    $assert->pageTextNotContains('Emu');
    $this->assertFacetsSummary(1, '(-) Before Mon, 08/16/2021 - 00:00');
    // Test between operator.
    $page->fillField('edit-authored-on-from-date', '2021-08-16');
    $page->fillField('edit-authored-on-to-date', '2021-08-17');
    $form->pressButton('Search');
    $this->assertCurrentUrl('test', ['f' => ['authored_on:2021-08-16~2021-08-17']]);
    $assert->elementsCount('css', '.views-row', 1);
    $assert->pageTextNotContains('Llama');
    $assert->pageTextContains('Emu');
    $this->assertFacetsSummary(1, '(-) Between Mon, 08/16/2021 - 00:00 and Tue, 08/17/2021 - 00:00');
  }


  /**
   * Test date range facets form as dependent facet.
   */
  public function testDateRangeFacetsFormWithDependencies(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $facet_name = 'Emu';
    $facet_id = 'emu';
    $this->createFacet($facet_name, $facet_id, 'type', 'page_1', 'views_page__test', FALSE);
    $facet = Facet::load($facet_id);
    $facet->setWidget('facets_form_dropdown');
    $facet->save();

    $depending_name = 'Llama';
    $depending_id = 'llama';
    $this->createFacet($depending_name, $depending_id, 'created', 'page_1', 'views_page__test', FALSE);
    $depending_facet = Facet::load($depending_id);
    $processor = [
      'processor_id' => 'dependent_processor',
      'weights' => ['build' => 5],
      'settings' => [
        $facet_id => [
          'enable' => TRUE,
          'condition' => 'values',
          'values' => 'bundle_1',
          'negate' => FALSE,
        ],
      ],
    ];
    $depending_facet->addProcessor($processor);
    $depending_facet->setWidget('facets_form_date_range');
    $depending_facet->save();

    // Place the Facets Form block.
    $this->drupalPlaceBlock('facets_form:search_api:views_page__test__page_1');
    $this->drupalGet('test');

    $assert->elementsCount('css', '.views-row', 2);
    $form = $assert->elementExists('css', 'form#facets-form');

    // Test that the dependent facet is not shown.
    $assert->elementExists('css', 'select#edit-emu--2', $form);
    $assert->elementNotExists('css', 'fieldset#edit-llama--2', $form);

    // Test that dependent facet exists when correct value is set.
    $page->selectFieldOption('emu[]', 'bundle_1');
    $assert->buttonExists('Search', $form)->press();
    $assert->elementsCount('css', '.views-row', 1);
    $assert->elementExists('css', 'select#edit-emu--2', $form);
    $assert->elementExists('css', 'fieldset#edit-llama--2', $form);

    // Test that dependent facet doesn't exist when other value is set.
    $page->selectFieldOption('emu[]', 'bundle_2');
    $assert->buttonExists('Search', $form)->press();
    $assert->elementsCount('css', '.views-row', 1);
    $assert->elementExists('css', 'select#edit-emu--2', $form);
    $assert->elementNotExists('css', 'fieldset#edit-llama--2', $form);

    // Test that dependent facet exists when all values are set.
    $page->selectFieldOption('emu[]', 'bundle_1', TRUE);
    $assert->buttonExists('Search', $form)->press();
    $assert->elementsCount('css', '.views-row', 2);
    $assert->elementExists('css', 'select#edit-emu--2', $form);
    $assert->elementExists('css', 'fieldset#edit-llama--2', $form);
  }

  /**
   * Asserts the facets summary result.
   *
   * @param int $expected_count
   *   The number of expected results.
   * @param string|null $expected_facet
   *   The expected.
   */
  protected function assertFacetsSummary(int $expected_count, ?string $expected_facet): void {
    $assert = $this->assertSession();

    $summary_count = \Drupal::translation()->formatPlural($expected_count, '1 result found', '@count results found');
    $assert->elementTextContains('css', '.source-summary-count', $summary_count);

    if (is_null($expected_facet)) {
      $assert->elementNotExists('css', '.facet-summary-item--facet');
      return;
    }

    $assert->elementsCount('css', '.facet-summary-item--facet', 1);
    $assert->elementTextContains('css', '.facet-summary-item--facet', $expected_facet);
  }

}
