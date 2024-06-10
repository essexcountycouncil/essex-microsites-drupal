<?php

namespace Drupal\Tests\localgov_microsites_group_term_ui\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the GroupTermSelection EntityReferenceSelection plugin.
 *
 * @group localgov_microsites_group_term_ui
 */
class GroupTermEntityReferenceTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_microsites_group_term_ui',
    'localgov_microsites_group_term_ui_test',
    'domain',
    'entity_test',
    'field',
    'group_term',
    'system',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');
    $this->installConfig([
      'user',
      'localgov_microsites_group_term_ui_test',
    ]);
  }

  /**
   * Tests an entity reference field restricted to a single vocabulary.
   */
  public function testGroupTermReference() {

    // Create a group.
    $group = $this->createGroup(['type' => 'default']);

    // Create two terms, one a group term.
    $term1 = Term::create([
      'name' => 'term1',
      'vid' => 'localgov_topic',
    ]);
    $term1->save();
    $group->addRelationship($term1, 'group_term:localgov_topic');
    $term2 = Term::create([
      'name' => 'term2',
      'vid' => 'localgov_topic',
    ]);
    $term2->save();

    // Create an entity reference field.
    $field_name = 'taxonomy_localgov_topic';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'translatable' => FALSE,
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
      'type' => 'entity_reference',
      'cardinality' => 1,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'entity_type' => 'entity_test',
      'bundle' => 'test_bundle',
      'settings' => [
        'handler' => 'group',
      ],
    ]);
    $field->save();

    // Initialise the field's entity reference handler.
    $handler = $this->container->get('plugin.manager.entity_reference_selection')->getSelectionHandler($field);

    // Check results with no group context.
    $result = $handler->getReferenceableEntities();
    $expected_result = [
      'localgov_topic' => [
        $term1->id() => $term1->getName(),
        $term2->id() => $term2->getName(),
      ],
    ];
    $this->assertSame($expected_result, $result);

    // Create a request for the current group.
    $request = new Request([], [], ['group' => $group]);
    \Drupal::requestStack()->push($request);

    // Check results with group context.
    $result = $handler->getReferenceableEntities();
    $expected_result = [
      'localgov_topic' => [
        $term1->id() => $term1->getName(),
      ],
    ];
    $this->assertSame($expected_result, $result);
  }

}
