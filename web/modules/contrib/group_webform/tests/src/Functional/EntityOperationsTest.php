<?php

namespace Drupal\Tests\group_webform\Functional;

use Drupal\Tests\group\Functional\EntityOperationsTest as GroupEntityOperationsTest;

/**
 * Tests that entity operations (do not) show up on the group overview.
 *
 * @see group_webform_entity_operation()
 *
 * @group group_webform
 */
class EntityOperationsTest extends GroupEntityOperationsTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_webform'];

  /**
   * {@inheritdoc}
   */
  public function provideEntityOperationScenarios() {
    $scenarios['withoutAccess'] = [
      [],
      ['group/1/nodes' => 'Nodes'],
    ];

    $scenarios['withAccess'] = [
      [],
      ['group/1/nodes' => 'Nodes'],
      [
        'view group',
        'access group_node overview'
      ],
    ];

    $scenarios['withAccessAndViews'] = [
      ['group/1/nodes' => 'Nodes'],
      [],
      [
        'view group',
        'access group_node overview'
      ],
      ['views'],
    ];

    return $scenarios;
  }

}
