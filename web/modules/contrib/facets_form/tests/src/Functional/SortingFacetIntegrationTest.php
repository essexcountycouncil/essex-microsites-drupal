<?php

declare(strict_types = 1);

namespace Drupal\Tests\facets_form\Functional;

use Behat\Mink\Exception\ExpectationException;
use Drupal\Core\Language\LanguageInterface;
use Drupal\facets\Entity\Facet;
use Drupal\search_api\Item\Field;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\facets\Functional\FacetsTestBase;
use Drupal\Tests\facets_form\Traits\FacetUrlTestTrait;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests the sorting of facets implementation.
 *
 * @group facets_form
 */
class SortingFacetIntegrationTest extends FacetsTestBase {

  use EntityReferenceTestTrait;
  use FacetUrlTestTrait;
  use TaxonomyTestTrait;

  /**
   * A taxonomy vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The parent terms.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $parents = [];

  /**
   * The terms.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $terms = [];

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
  public function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->adminUser);

    // Create hierarchical terms in a new vocabulary.
    $this->vocabulary = $this->createVocabulary();
    $this->createHierarchialTermStructure();

    // Default content that is extended with a term reference field below.
    $this->setUpExampleStructure();

    // Create a taxonomy_term_reference field on the article and item.
    $this->fieldName = 'tax_ref_field';
    $fieldLabel = 'Taxonomy reference field';

    $this->createEntityReferenceField('entity_test_mulrev_changed', 'article', $this->fieldName, $fieldLabel, 'taxonomy_term');
    $this->createEntityReferenceField('entity_test_mulrev_changed', 'item', $this->fieldName, $fieldLabel, 'taxonomy_term');

    $this->insertExampleContent();
    $this->assertSame(6, $this->indexItems('database_search_index'));

    // Add fields to index.
    $index = $this->getIndex();

    $term_field = new Field($index, $this->fieldName);
    $term_field->setType('integer');
    $term_field->setPropertyPath($this->fieldName);
    $term_field->setDatasourceId('entity:entity_test_mulrev_changed');
    $term_field->setLabel($fieldLabel);
    $index->addField($term_field);

    $index->save();
    $this->indexItems($this->indexId);

    $facet_name = 'hierarchical facet';
    $facet_id = 'hierarchical_facet';
    $this->facetEditPage = 'admin/config/search/facets/' . $facet_id . '/edit';
    $this->createFacet($facet_name, $facet_id, $this->fieldName);
    $this->blocks = NULL;
  }

  /**
   * Tests sorting of hierarchy in a Checkboxes field.
   */
  public function testHierarchySortingCheckboxes(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalLogin($this->drupalCreateUser([
      'administer search_api',
      'administer facets',
      'access administration pages',
      'administer nodes',
      'access content overview',
      'administer content types',
      'administer blocks',
    ]));
    $facet = Facet::load('hierarchical_facet');
    $facet->setWidget('facets_form_checkbox', ['indent_class' => 'super-indented']);
    $facet->setOnlyVisibleWhenFacetSourceIsVisible(FALSE);
    $facet->save();
    $block = $this->drupalPlaceBlock('facets_form:search_api:views_page__search_api_test_view__page_1');
    $this->drupalGet('admin/structure/block/manage/' . $block->id());
    $page->checkField($facet->getName());
    $page->pressButton('Save block');

    /*
     * Test order by widget asc.
     * Parent 1
     * * Child 1
     * * Child 2
     * Parent 2
     * * Child 3
     * * Child 4
     */
    $edit = [
      'facet_settings[expand_hierarchy]' => '1',
      'facet_settings[keep_hierarchy_parents_active]' => FALSE,
      'facet_settings[use_hierarchy]' => '1',
      'facet_settings[translate_entity][status]' => '1',
      'facet_sorting[display_value_widget_order][status]' => '1',
      'facet_sorting[display_value_widget_order][settings][sort]' => 'ASC',
      'facet_sorting[count_widget_order][status]' => '0',
      'facet_sorting[active_widget_order][status]' => '0',
    ];
    $this->drupalGet($this->facetEditPage);
    $this->submitForm($edit, 'Save');
    $this->drupalGet('search-api-test-fulltext');
    $this->responseContentHasOrder([
      'Parent 1', 'Child 1',
      'Child 2', 'Parent 2',
      'Child 3', 'Child 4',
    ]);

    /*
     * Test order by active widget asc.
     * Parent 2
     * * Child 3
     * * Child 4
     * Parent 1
     * * Child 1
     * * Child 2
     */
    $edit = [
      'facet_sorting[display_value_widget_order][status]' => '0',
      'facet_sorting[active_widget_order][status]' => '1',
      'facet_sorting[active_widget_order][settings][sort]' => 'ASC',
    ];
    $this->drupalGet($this->facetEditPage);
    $this->submitForm($edit, 'Save');
    $this->drupalGet('search-api-test-fulltext');
    $form = $assert->elementExists('css', 'form#facets-form');
    $form->checkField('Parent 2');
    $form->pressButton('Search');
    $this->responseContentHasOrder([
      'Parent 2', 'Child 3',
      'Child 4', 'Parent 1',
      'Child 1', 'Child 2',
    ]);

    /*
     * Test order by active widget desc.
     * Parent 1
     * * Child 1
     * * Child 2
     * Parent 2
     * * Child 3
     * * Child 4
     */
    $edit = [
      'facet_sorting[active_widget_order][settings][sort]' => 'DESC',
    ];
    $this->drupalGet($this->facetEditPage);
    $this->submitForm($edit, 'Save');
    $this->drupalGet('search-api-test-fulltext');
    $form->checkField('Parent 2');
    $form->pressButton('Search');
    $this->assertCurrentUrl('search-api-test-fulltext', [
      'f' => ['hierarchical_facet:' . $this->parents['Parent 2']->id()],
    ]);
    $this->responseContentHasOrder([
      'Parent 1', 'Child 1',
      'Child 2', 'Parent 2',
      'Child 3', 'Child 4',
    ]);

    /*
     * Test order by count widget desc.
     * Parent 1
     * * Child 1
     * * Child 2
     * Parent 2
     * * Child 3
     * * Child 4
     */
    $edit = [
      'facet_sorting[active_widget_order][status]' => '0',
      'facet_sorting[count_widget_order][status]' => '1',
      'facet_sorting[count_widget_order][settings][sort]' => 'DESC',
    ];
    $this->drupalGet($this->facetEditPage);
    $this->submitForm($edit, 'Save');
    $this->drupalGet('search-api-test-fulltext');
    $form = $assert->elementExists('css', 'form#facets-form');
    $form->checkField('Parent 2');
    $form->pressButton('Search');
    $this->assertCurrentUrl('search-api-test-fulltext', [
      'f' => ['hierarchical_facet:' . $this->parents['Parent 2']->id()],
    ]);
    $assert->checkboxChecked('Parent 2');
    $assert->elementsCount('css', '.views-row', 1);
    $this->responseContentHasOrder([
      'Parent 1', 'Child 1',
      'Child 2', 'Parent 2',
      'Child 3', 'Child 4',
    ]);

    /*
     * Test order by count widget asc.
     * Parent 1
     * * Child 1
     * * Child 2
     * Parent 2
     * * Child 3
     * * Child 4
     */
    $edit = [
      'facet_sorting[count_widget_order][settings][sort]' => 'ASC',
    ];
    $this->drupalGet($this->facetEditPage);
    $this->submitForm($edit, 'Save');
    $this->drupalGet('search-api-test-fulltext');
    $form->checkField('Parent 1');
    $form->uncheckField('Parent 2');
    $form->pressButton('Search');
    $this->assertCurrentUrl('search-api-test-fulltext', [
      'f' => ['hierarchical_facet:' . $this->parents['Parent 1']->id()],
    ]);
    $assert->elementsCount('css', '.views-row', 1);
    $this->responseContentHasOrder([
      'Parent 1', 'Child 1',
      'Child 2', 'Parent 2',
      'Child 3', 'Child 4',
    ]);
    $page->clickLink('Clear filters');
    $this->assertCurrentUrl('search-api-test-fulltext');
    $assert->elementsCount('css', '.views-row', 6);
  }

  /**
   * Tests sorting of hierarchy in a Dropdown field.
   */
  public function testHierarchySortingDropdown(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalLogin($this->drupalCreateUser([
      'administer search_api',
      'administer facets',
      'access administration pages',
      'administer nodes',
      'access content overview',
      'administer content types',
      'administer blocks',
    ]));
    $facet = Facet::load('hierarchical_facet');
    $facet->setWidget('facets_form_dropdown', ['child_items_prefix' => '-']);
    $facet->save();
    $block = $this->drupalPlaceBlock('facets_form:search_api:views_page__search_api_test_view__page_1');
    $this->drupalGet('admin/structure/block/manage/' . $block->id());
    $page->checkField($facet->getName());
    $page->pressButton('Save block');

    /*
     * Test order by widget asc.
     * Parent 1
     * * Child 1
     * * Child 2
     * Parent 2
     * * Child 3
     * * Child 4
     */
    $edit = [
      'facet_settings[expand_hierarchy]' => '1',
      'facet_settings[keep_hierarchy_parents_active]' => FALSE,
      'facet_settings[use_hierarchy]' => '1',
      'facet_settings[translate_entity][status]' => '1',
      'facet_sorting[display_value_widget_order][status]' => '1',
      'facet_sorting[display_value_widget_order][settings][sort]' => 'ASC',
      'facet_sorting[count_widget_order][status]' => '0',
      'facet_sorting[active_widget_order][status]' => '0',
    ];
    $this->drupalGet($this->facetEditPage);
    $this->submitForm($edit, 'Save');
    $this->drupalGet('search-api-test-fulltext');
    $this->responseContentHasOrder([
      'Parent 1', '- Child 1',
      '- Child 2', 'Parent 2',
      '- Child 3', '- Child 4',
    ]);

    /*
     * Test order by active widget asc.
     * Parent 2
     * * Child 3
     * * Child 4
     * Parent 1
     * * Child 1
     * * Child 2
     */
    $edit = [
      'facet_sorting[display_value_widget_order][status]' => '0',
      'facet_sorting[active_widget_order][status]' => '1',
      'facet_sorting[active_widget_order][settings][sort]' => 'ASC',
    ];
    $this->drupalGet($this->facetEditPage);
    $this->submitForm($edit, 'Save');
    $this->drupalGet('search-api-test-fulltext');
    $form = $assert->elementExists('css', 'form#facets-form');
    $form->selectFieldOption('hierarchical_facet[]', 'Parent 2');
    $form->pressButton('Search');
    $this->responseContentHasOrder([
      'Parent 2', '- Child 3',
      '- Child 4', 'Parent 1',
      '- Child 1', '- Child 2',
    ]);

    /*
     * Test order by active widget desc.
     * Parent 1
     * * Child 1
     * * Child 2
     * Parent 2
     * * Child 3
     * * Child 4
     */
    $edit = [
      'facet_sorting[active_widget_order][settings][sort]' => 'DESC',
    ];
    $this->drupalGet($this->facetEditPage);
    $this->submitForm($edit, 'Save');
    $this->drupalGet('search-api-test-fulltext');
    $form->selectFieldOption('hierarchical_facet[]', 'Parent 2');
    $form->pressButton('Search');
    $this->responseContentHasOrder([
      'Parent 1', '- Child 1',
      '- Child 2', 'Parent 2',
      '- Child 3', '- Child 4',
    ]);

    /*
     * Test order by count widget desc.
     * Parent 1
     * * Child 1
     * * Child 2
     * Parent 2
     * * Child 3
     * * Child 4
     */
    $edit = [
      'facet_sorting[active_widget_order][status]' => '0',
      'facet_sorting[count_widget_order][status]' => '1',
      'facet_sorting[count_widget_order][settings][sort]' => 'DESC',
    ];
    $this->drupalGet($this->facetEditPage);
    $this->submitForm($edit, 'Save');
    $this->drupalGet('search-api-test-fulltext');
    $form = $assert->elementExists('css', 'form#facets-form');
    $form->selectFieldOption('hierarchical_facet[]', 'Parent 2');
    $form->pressButton('Search');
    $this->assertCurrentUrl('search-api-test-fulltext', [
      'f' => ['hierarchical_facet:' . $this->parents['Parent 2']->id()],
    ]);
    $assert->elementsCount('css', '.views-row', 1);
    $this->responseContentHasOrder([
      'Parent 1', '- Child 1',
      '- Child 2', 'Parent 2',
      '- Child 3', '- Child 4',
    ]);

    /*
     * Test order by count widget asc.
     * Parent 1
     * * Child 1
     * * Child 2
     * Parent 2
     * * Child 3
     * * Child 4
     */
    $edit = [
      'facet_sorting[count_widget_order][settings][sort]' => 'ASC',
    ];
    $this->drupalGet($this->facetEditPage);
    $this->submitForm($edit, 'Save');
    $this->drupalGet('search-api-test-fulltext');
    $form->selectFieldOption('hierarchical_facet[]', 'Parent 1');
    $form->pressButton('Search');
    $this->assertCurrentUrl('search-api-test-fulltext', [
      'f' => ['hierarchical_facet:' . $this->parents['Parent 1']->id()],
    ]);
    $assert->elementsCount('css', '.views-row', 1);
    $this->responseContentHasOrder([
      'Parent 1', '- Child 1',
      '- Child 2', 'Parent 2',
      '- Child 3', '- Child 4',
    ]);

    $page->clickLink('Clear filters');
    $assert->addressNotEquals('f[0]=hierarchical_facet');
    $assert->elementsCount('css', '.views-row', 6);
  }

  /**
   * Sets up a term structure for our test.
   */
  protected function createHierarchialTermStructure(): void {
    // Generate 2 parent terms.
    foreach (['Parent 1', 'Parent 2'] as $name) {
      $this->parents[$name] = Term::create([
        'name' => $name,
        'description' => '',
        'vid' => $this->vocabulary->id(),
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ]);
      $this->parents[$name]->save();
    }

    // Generate 4 child terms.
    foreach (range(1, 4) as $i) {
      $this->terms[$i] = Term::create([
        'name' => sprintf('Child %d', $i),
        'description' => '',
        'vid' => $this->vocabulary->id(),
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ]);
      $this->terms[$i]->save();
    }

    // Build up the hierarchy.
    $this->terms[1]->parent = [$this->parents['Parent 1']->id()];
    $this->terms[1]->save();

    $this->terms[2]->parent = [$this->parents['Parent 1']->id()];
    $this->terms[2]->save();

    $this->terms[3]->parent = [$this->parents['Parent 2']->id()];
    $this->terms[3]->save();

    $this->terms[4]->parent = [$this->parents['Parent 2']->id()];
    $this->terms[4]->save();
  }

  /**
   * Creates several test entities with the term-reference field.
   */
  protected function insertExampleContent(): void {
    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrev_changed');

    $count = $entity_test_storage->getQuery()
      ->count()
      ->accessCheck(FALSE)
      ->execute();

    $this->entities[1] = $entity_test_storage->create([
      'name' => 'foo bar baz',
      'body' => 'test test',
      'type' => 'item',
      'keywords' => ['orange'],
      'category' => 'item_category',
      $this->fieldName => [$this->parents['Parent 1']->id()],
    ]);
    $this->entities[1]->save();

    $this->entities[2] = $entity_test_storage->create([
      'name' => 'foo test',
      'body' => 'bar test',
      'type' => 'item',
      'keywords' => ['orange', 'apple', 'grape', 1],
      'category' => 'item_category',
      $this->fieldName => [$this->parents['Parent 2']->id()],
    ]);
    $this->entities[2]->save();

    $this->entities[3] = $entity_test_storage->create([
      'name' => 'bar',
      'body' => 'test foobar',
      'type' => 'item',
      $this->fieldName => [$this->terms[1]->id()],
    ]);
    $this->entities[3]->save();

    $this->entities[4] = $entity_test_storage->create([
      'name' => 'foo baz',
      'body' => 'test test test',
      'type' => 'article',
      'keywords' => ['apple', 'strawberry', 'grape', 1, 2],
      'category' => 'article_category',
      $this->fieldName => [$this->terms[2]->id()],
    ]);
    $this->entities[4]->save();

    $this->entities[5] = $entity_test_storage->create([
      'name' => 'bar baz',
      'body' => 'foo',
      'type' => 'article',
      'keywords' => ['orange', 'strawberry', 'grape', 'banana'],
      'category' => 'article_category',
      $this->fieldName => [$this->terms[3]->id()],
    ]);
    $this->entities[5]->save();
    $this->entities[6] = $entity_test_storage->create([
      'name' => 'bar baz',
      'body' => 'foo',
      'type' => 'article',
      'keywords' => ['orange', 'strawberry', 'grape', 'banana'],
      'category' => 'article_category',
      $this->fieldName => [$this->terms[4]->id()],
    ]);
    $this->entities[6]->save();
    $count = $entity_test_storage->getQuery()
      ->count()
      ->accessCheck(FALSE)
      ->execute() - $count;
    $this->assertSame(6, $count);
  }

  /**
   * Asserts that several pieces of markup are in a given order in the page.
   *
   * @param string[] $items
   *   An ordered list of strings.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When any of the given string is not found.
   *
   * @todo Remove this once https://www.drupal.org/node/2817657 is committed.
   */
  protected function responseContentHasOrder(array $items) {
    $session = $this->getSession();
    $text = $session->getPage()->getHtml();
    $strings = [];
    foreach ($items as $item) {
      if (($pos = strpos($text, $item)) === FALSE) {
        throw new ExpectationException("Cannot find '$item' in the page", $session->getDriver());
      }
      $strings[$pos] = $item;
    }
    ksort($strings);
    $ordered = implode(', ', array_map(function ($item) {
      return "'$item'";
    }, $items));
    $this->assertSame($items, array_values($strings), "Found strings, ordered as: $ordered.");
  }

}
