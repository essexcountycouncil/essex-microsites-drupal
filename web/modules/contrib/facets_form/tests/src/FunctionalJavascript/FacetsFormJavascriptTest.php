<?php

declare(strict_types = 1);

namespace Drupal\Tests\facets_form\FunctionalJavascript;

use Drupal\Component\Serialization\Json;
use Drupal\facets\Entity\Facet;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\facets\Functional\BlockTestTrait;
use Drupal\Tests\facets\Functional\ExampleContentTrait;

/**
 * Tests the Facets Form Javascript functionality.
 *
 * @group facets_form
 */
class FacetsFormJavascriptTest extends WebDriverTestBase {

  use ExampleContentTrait;
  use BlockTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'facets_form',
    'facets_form_test',
    'facets_search_api_dependency',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Keep track of user interaction.
   *
   * This is needed to distinguish between results, as the DOM has some latency
   * when is updated and have to make sure we're asserting the correct DOM
   * element.
   *
   * @var int
   */
  protected $interactions = 0;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->setUpExampleStructure();
    $this->insertExampleContent();
    $this->assertSame(5, $this->indexItems('database_search_index'));
    entity_test_create_bundle('page', NULL, 'entity_test_mulrev_changed');
  }

  /**
   * Tests custom event dispatching.
   */
  public function testCustomEventDispatching(): void {
    $this->createFacet('Emu', 'emu', 'type', 'page_1', 'views_page__search_api_test_view', FALSE);
    $facet = Facet::load('emu');
    $facet->setOnlyVisibleWhenFacetSourceIsVisible(FALSE);
    $facet->setWidget('facets_form_checkbox');
    $facet->save();
    $this->createFacet('Llama', 'llama', 'type', 'page_1', 'views_page__search_api_test_view', FALSE);
    $facet = Facet::load('llama');
    $facet->setOnlyVisibleWhenFacetSourceIsVisible(FALSE);
    $facet->setWidget('facets_form_dropdown');
    $facet->save();

    $this->placeBlock('facets_form:search_api:views_page__search_api_test_view__page_1');
    $this->drupalGet('search-api-test-fulltext');

    $this->addFilter('emu', 'article');
    $this->assertFilters([
      'emu' => ['article'],
    ],
    [
      'added' => ['article'],
      'removed' => [],
    ]);

    $this->addFilter('llama', 'item');
    $this->assertFilters([
      'emu' => ['article'],
      'llama' => ['item'],
    ],
    [
      'added' => ['item'],
      'removed' => [],
    ]);

    $this->addFilter('llama', 'article');
    $this->assertFilters([
      'emu' => ['article'],
      'llama' => ['item', 'article'],
    ],
    [
      'added' => ['article'],
      'removed' => [],
    ]);

    $this->addFilter('emu', 'item');
    $this->assertFilters([
      'emu' => ['item', 'article'],
      'llama' => ['item', 'article'],
    ],
    [
      'added' => ['item'],
      'removed' => [],
    ]);

    $this->removeFilter('emu', 'article');
    $this->assertFilters([
      'emu' => ['item'],
      'llama' => ['item', 'article'],
    ],
    [
      'added' => [],
      'removed' => ['article'],
    ]);

    $this->removeFilter('emu', 'item');
    $this->assertFilters([
      'llama' => ['item', 'article'],
    ],
    [
      'added' => [],
      'removed' => ['item'],
    ]);

    $entity_test_storage = \Drupal::entityTypeManager()->getStorage('entity_test_mulrev_changed');
    $entity_test_storage->create([
      'name' => 'foo',
      'body' => 'test',
      'type' => 'page',
      'keywords' => ['orange'],
      'category' => 'item_category',
    ])->save();
    $this->assertSame(1, $this->indexItems('database_search_index'));

    // Since we visit the page again, we reset the interactions.
    $this->drupalGet('search-api-test-fulltext');
    $this->interactions = 0;

    $this->addFilter('emu', 'page');
    $this->assertFilters(
      [
        'emu' => ['page'],
      ],
      [
        'added' => ['page'],
        'removed' => [],
      ]
    );

    $this->removeFilter('emu', 'page');
    $this->assertFilters(
      [],
      [
        'added' => [],
        'removed' => ['page'],
      ]
    );

  }

  /**
   * Expands the filter selection.
   *
   * @param string $facet
   *   The facet ID.
   * @param string $value
   *   The value to be selected.
   */
  protected function addFilter(string $facet, string $value): void {
    $this->interactions++;
    $page = $this->getSession()->getPage();
    if ($facet === 'emu') {
      $page->checkField($value);
    }
    elseif ($facet === 'llama') {
      $page->selectFieldOption('llama[]', $value, TRUE);
    }
    else {
      throw new \InvalidArgumentException("Invalid facet '{$facet}'");
    }
  }

  /**
   * Removes a filter from selection.
   *
   * @param string $facet
   *   The facet ID.
   * @param string $value
   *   The value to be removed.
   */
  protected function removeFilter(string $facet, string $value): void {
    $this->interactions++;
    $page = $this->getSession()->getPage();
    if ($facet === 'emu') {
      $page->uncheckField($value);
    }
    elseif ($facet === 'llama') {
      $select = $page->findField('llama[]');
      $values = $select->getValue();
      if (($index = array_search($value, $values, TRUE) === FALSE)) {
        throw new \InvalidArgumentException("Cannot unselect '{$value}' as is not selected");
      }
      unset($values[$index]);
      $select->setValue($values);
    }
    else {
      throw new \InvalidArgumentException("Invalid facet '{$facet}'");
    }
  }

  /**
   * Asserts last selection event was dispatching.
   *
   * @param array $expected_filters
   *   Expected list of filters passed to the event listener.
   * @param array $expected_diff
   *   Expected changes of filters in this particular input.
   */
  protected function assertFilters(array $expected_filters, array $expected_diff): void {
    $node = $this->assertSession()->waitForElement('css', "div#test-{$this->interactions}");
    $this->assertSame("test-{$this->interactions}", $node->getAttribute('id'));
    $actual = Json::decode($node->getText());
    $this->assertSame($expected_filters, $actual['filters']);
    $this->assertSame($expected_diff, $actual['diff']);
  }

}
