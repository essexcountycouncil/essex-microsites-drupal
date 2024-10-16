<?php

declare(strict_types = 1);

namespace Drupal\Tests\facets_form\Functional;

use Drupal\facets\Entity\Facet;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\facets\Functional\BlockTestTrait;
use Drupal\Tests\facets\Functional\ExampleContentTrait;
use Drupal\Tests\facets_form\Traits\FacetUrlTestTrait;

/**
 * Test the facets form.
 *
 * @group facets_form
 */
class IntegrationTest extends BrowserTestBase {

  use ExampleContentTrait;
  use BlockTestTrait;
  use FacetUrlTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'facets_form',
    'facets_search_api_dependency',
    'node',
    'search_api',
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
   * Test the facets form.
   */
  public function testFacetsForm(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalLogin($this->drupalCreateUser(['administer blocks']));
    $this->createFacet('Emu', 'emu');
    $this->createFacet('Llama', 'llama');
    $facet = Facet::load('llama');
    $facet->setOnlyVisibleWhenFacetSourceIsVisible(FALSE);
    $facet->setWidget('facets_form_dropdown');
    $facet->save();
    // Place the Facets Form block for a view page source.
    $block = $this->drupalPlaceBlock('facets_form:search_api:views_page__search_api_test_view__page_1');
    $this->drupalGet('admin/structure/block/manage/' . $block->id());
    $config_form = $assert->elementExists('css', '.block-form');
    $assert->fieldValueEquals('Submit button', 'Search');
    $assert->fieldValueEquals('Reset button', 'Clear filters');
    $config_form->fillField('Submit button', 'Apply');
    $config_form->fillField('Reset button', 'Reset');
    $config_form->checkField('Llama');
    $config_form->pressButton('Save block');
    $this->drupalGet('search-api-test-fulltext');
    $assert->elementsCount('css', '.views-row', 5);
    $form = $assert->elementExists('css', 'form#facets-form');

    // The form contains only the widget from the module.
    $assert->elementExists('css', 'select#edit-llama--2', $form);
    $assert->elementNotExists('css', 'select#edit-emu--2', $form);
    $assert->elementNotExists('css', 'select#edit-alpaca--2', $form);

    // The form submits and filters the results.
    $page->selectFieldOption('llama[]', 'article');
    $assert->buttonExists('Apply', $form)->press();
    $this->assertCurrentUrl('search-api-test-fulltext', ['f' => ['llama:article']]);
    $assert->elementsCount('css', '.views-row', 2);

    $page->selectFieldOption('llama[]', 'item');
    $assert->buttonExists('Apply', $form)->press();
    $this->assertCurrentUrl('search-api-test-fulltext', ['f' => ['llama:item']]);
    $assert->elementsCount('css', '.views-row', 3);

    $page->clickLink('Reset');
    $this->assertCurrentUrl('search-api-test-fulltext');
    $assert->elementsCount('css', '.views-row', 5);

    // Test the CheckboxWidget widget.
    $facet->setWidget('facets_form_checkbox', ['indent_class' => 'super-indented']);
    $facet->save();
    // Pass an arbitrary query string in order to check its preservation.
    $this->drupalGet('search-api-test-fulltext', [
      'query' => [
        'foo' => 'bar',
        'baz' => ['qux', 'quux'],
      ],
    ]);
    // Check form submit without any filter.
    $form->pressButton('Apply');
    // Check query string preservation when submitting with no filter changes.
    $this->assertCurrentUrl('search-api-test-fulltext', [
      'foo' => 'bar',
      'baz' => ['qux', 'quux'],
    ]);
    $form->checkField('item');
    $form->pressButton('Apply');
    // Check query string preservation after submitting with filter changes.
    $this->assertCurrentUrl('search-api-test-fulltext', [
      'foo' => 'bar',
      'baz' => ['qux', 'quux'],
      'f' => ['llama:item'],
    ]);
    $assert->checkboxChecked('item', $form);
    $assert->elementsCount('css', '.views-row', 3);
    $form->checkField('article');
    $form->pressButton('Apply');
    // Check query string preservation after submitting with filter changes.
    $this->assertCurrentUrl('search-api-test-fulltext', [
      'foo' => 'bar',
      'baz' => ['qux', 'quux'],
      'f' => ['llama:article', 'llama:item'],
    ]);
    $assert->checkboxChecked('item', $form);
    $assert->checkboxChecked('article', $form);
    $assert->elementsCount('css', '.views-row', 5);
    // Check query string preservation after resetting the filters.
    $page->clickLink('Reset');
    $this->assertCurrentUrl('search-api-test-fulltext', [
      'foo' => 'bar',
      'baz' => ['qux', 'quux'],
    ]);

    // Change configured facets.
    $this->createFacet('Alpaca', 'alpaca');
    $facet = Facet::load('alpaca');
    $facet->setWidget('facets_form_dropdown');
    $facet->save();
    $this->drupalGet('admin/structure/block/manage/' . $block->id());
    $assert->fieldNotExists('Emu');
    $assert->fieldExists('Llama');
    $assert->fieldExists('Alpaca');
    $page->checkField('Alpaca');
    $page->uncheckField('Llama');
    $page->pressButton('Save block');
    $this->drupalGet('search-api-test-fulltext');
    $form = $assert->elementExists('css', 'form#facets-form');
    $assert->elementExists('css', 'select#edit-alpaca--2', $form);
    $assert->elementNotExists('css', 'select#edit-llama--2', $form);
    $assert->elementNotExists('css', 'select#edit-emu--2', $form);

    // Drupal caches forms, so we make sure the reset link has the correct url
    // parameters.
    $this->drupalLogout();
    // Visit the page as Anonymous with query parameters.
    $this->drupalGet('search-api-test-fulltext', [
      'query' => [
        'foo' => 'bar',
        'baz' => ['qux', 'quux'],
      ],
    ]);
    $this->assertCurrentUrl('search-api-test-fulltext', [
      'foo' => 'bar',
      'baz' => ['qux', 'quux'],
    ]);
    // Change the query parameters and assert the reset link also changed.
    $this->drupalGet('search-api-test-fulltext', [
      'query' => [
        'mip' => 'map',
      ],
    ]);
    $this->assertCurrentUrl('search-api-test-fulltext', [
      'mip' => 'map',
    ]);
    $this->assertSame(
      $this->formatUrlForDiff('search-api-test-fulltext', [
        'mip' => 'map',
      ]),
      $this->formatUrlForDiff($page->findLink('Reset')->getAttribute('href')),
    );
  }

  /**
   * Test facets form as dependent facet.
   */
  public function testFacetsFormWithDependencies(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $facet_name = 'Emu';
    $facet_id = 'emu';
    $this->createFacet($facet_name, $facet_id);
    $facet = Facet::load($facet_id);
    $facet->setWidget('facets_form_dropdown');
    $facet->save();

    $depending_name = 'Llama';
    $depending_id = 'llama';
    $this->createFacet($depending_name, $depending_id, 'category');
    $depending_facet = Facet::load($depending_id);
    $processor = [
      'processor_id' => 'dependent_processor',
      'weights' => ['build' => 5],
      'settings' => [
        $facet_id => [
          'enable' => TRUE,
          'condition' => 'values',
          'values' => 'item',
          'negate' => FALSE,
        ],
      ],
    ];
    $depending_facet->addProcessor($processor);
    $depending_facet->setWidget('facets_form_dropdown');
    $depending_facet->save();

    // Place the Facets Form block.
    $settings['button']['label']['submit'] = 'Refine';
    $this->drupalPlaceBlock('facets_form:search_api:views_page__search_api_test_view__page_1', $settings);
    $this->drupalGet('search-api-test-fulltext');

    $assert->elementsCount('css', '.views-row', 5);
    $form = $assert->elementExists('css', 'form#facets-form');

    // Test that the dependent facet is not shown.
    $assert->elementExists('css', 'select#edit-emu--2', $form);
    $assert->elementNotExists('css', 'select#edit-llama--2', $form);

    // Test that dependent facet exists when correct value is set.
    $page->selectFieldOption('emu[]', 'item');
    $assert->buttonExists('Refine', $form)->press();
    $this->assertCurrentUrl('search-api-test-fulltext', ['f' => ['emu:item']]);
    $assert->elementsCount('css', '.views-row', 3);
    $assert->elementExists('css', 'select#edit-emu--2', $form);
    $assert->elementExists('css', 'select#edit-llama--2', $form);

    // Test that dependent facet doesn't exist when other value is set.
    $page->selectFieldOption('emu[]', 'article');
    $assert->buttonExists('Refine', $form)->press();
    $this->assertCurrentUrl('search-api-test-fulltext', ['f' => ['emu:article']]);
    $assert->elementsCount('css', '.views-row', 2);
    $assert->elementExists('css', 'select#edit-emu--2', $form);
    $assert->elementNotExists('css', 'select#edit-llama--2', $form);
  }

}
