<?php

declare(strict_types = 1);

namespace Drupal\Tests\facets_form\Kernel\Plugin\facets\widget;

use Drupal\facets\Entity\Facet;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\facets_form\Traits\FacetsFormWidgetTestTrait;

/**
 * @coversDefaultClass \Drupal\facets_form\Plugin\facets\widget\DropdownWidget
 * @group facets_form
 */
class DropdownWidgetTest extends KernelTestBase {

  use FacetsFormWidgetTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'facets',
    'facets_form',
    'facets_form_test',
  ];

  /**
   * Test the widget with empty items and different config.
   */
  public function testWithEmptyItems(): void {
    // Empty results should return an empty build.
    $facet = new Facet(['id' => 'foo'], 'facets_facet');
    $facet->setFacetSourceId('facets_form_test');
    $facet->setWidget('facets_form_dropdown');
    $facet->setResults($this->getResults($facet, []));
    $build = $facet->getWidgetInstance()->build($facet);

    $this->assertEmpty($build);

    // Exception the disabled_on_empty behaviour, we still want the build but
    // disabled, to indicate to the user that there are no results.
    $facet->setWidget('facets_form_dropdown', ['disabled_on_empty' => TRUE]);
    $build = $facet->getWidgetInstance()->build($facet)['foo'];

    $this->assertSame('select', $build['#type']);
    $this->assertSame('invisible', $build['#title_display']);
    $this->assertEmpty($build['#default_value']);
    $this->assertTrue($build['#multiple']);
    $this->assertTrue($build['#disabled']);
    $this->assertEmpty($build['#options']);
  }

  /**
   * Test the Facets Form dropdown widget build.
   *
   * @param array $facet_values
   *   Facet entity values.
   * @param array $widget_config
   *   The widget configuration.
   * @param array $data
   *   The used to build the results.
   * @param array $active_items
   *   Active items.
   * @param string $expected_title_display
   *   Select '#title_display' expectation.
   * @param array $expected_default_values
   *   Select '#default_value' expectation.
   * @param bool $expected_multiple
   *   Select element '#multiple' expectation.
   * @param bool $expected_disabled
   *   Select element '#disabled' expectation.
   * @param array $expected_options
   *   Select element '#options' expectation.
   *
   * @dataProvider providerTestPlugin
   *
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   */
  public function testPlugin(
    array $facet_values,
    array $widget_config,
    array $data,
    array $active_items,
    string $expected_title_display,
    array $expected_default_values,
    bool $expected_multiple,
    bool $expected_disabled,
    array $expected_options
  ): void {
    $facet = new Facet(['id' => 'foo'] + $facet_values, 'facets_facet');
    $facet->setFacetSourceId('facets_form_test');
    $facet->setWidget('facets_form_dropdown', $widget_config);
    $facet->setResults($this->getResults($facet, $data, $active_items));

    $build = $facet->getWidgetInstance()->build($facet)['foo'];
    $this->assertSame('select', $build['#type']);
    $this->assertSame($expected_title_display, $build['#title_display']);
    $this->assertEquals($expected_default_values, $build['#default_value']);
    $this->assertSame($expected_multiple, $build['#multiple']);
    $this->assertSame($expected_disabled, $build['#disabled']);
    $this->assertEquals($expected_options, $build['#options']);
  }

  /**
   * Provides test cases for ::testPlugin().
   *
   * @return array[]
   *   Test cases.
   */
  public function providerTestPlugin(): array {
    return [
      'default' => [
        [
          'show_title' => TRUE,
        ],
        [],
        [
          '1' => [
            '1.1' => [
              '1.1.1' => [],
            ],
            '1.2' => [],
            '1.3' => [
              '1.3.1' => [],
            ],
          ],
          '2' => [
            '2.1' => [],
          ],
        ],
        ['1.1', '2'],
        'before',
        ['1.1', '2'],
        TRUE,
        FALSE,
        [
          '1' => 'One',
          '1.1' => '- One.One',
          '1.1.1' => '-- One.One.One',
          '1.2' => '- One.Two',
          '1.3' => '- One.Three',
          '1.3.1' => '-- One.Three.One',
          '2' => 'Two',
          '2.1' => '- Two.One',
        ],
      ],
      'different_option_label' => [
        [
          'show_only_one_result' => TRUE,
        ],
        [
          'default_option_label' => 'Select',
        ],
        [
          '1' => [],
          '2' => [],
        ],
        ['1'],
        'invisible',
        ['1'],
        FALSE,
        FALSE,
        [
          '' => 'Select',
          '1' => 'One',
          '2' => 'Two',
        ],
      ],
      'child_item_prefix_asterix' => [
        [],
        [
          'child_items_prefix' => '*',
        ],
        [
          '1' => [
            '1.1' => [
              '1.1.1' => [],
            ],
          ],
        ],
        ['1.1.1'],
        'invisible',
        ['1.1.1'],
        TRUE,
        FALSE,
        [
          '1' => 'One',
          '1.1' => '* One.One',
          '1.1.1' => '** One.One.One',
        ],
      ],
      'child_item_prefix_space' => [
        [],
        [
          'child_items_prefix' => ' ',
        ],
        [
          '1' => [
            '1.1' => [
              '1.1.1' => [],
            ],
          ],
        ],
        [],
        'invisible',
        [],
        TRUE,
        FALSE,
        [
          '1' => 'One',
          '1.1' => '&nbsp; One.One',
          '1.1.1' => '&nbsp;&nbsp; One.One.One',
        ],
      ],
      'with_show_number' => [
        [],
        [
          'show_numbers' => TRUE,
        ],
        [
          '1' => [],
          '2' => [],
        ],
        ['1'],
        'invisible',
        ['1'],
        TRUE,
        FALSE,
        [
          '1' => 'One (1)',
          '2' => 'Two (2)',
        ],
      ],
      'pick_one_with_hash' => [
        [
          'show_only_one_result' => TRUE,
          'show_title' => TRUE,
        ],
        [
          'default_option_label' => 'Pick one',
          'child_items_prefix' => '#',
          'show_numbers' => TRUE,
        ],
        [
          '1' => [
            '1.1' => [
              '1.1.1' => [],
            ],
            '1.2' => [],
            '1.3' => [
              '1.3.1' => [],
            ],
          ],
          '2' => [
            '2.1' => [],
          ],
        ],
        ['1.1', '2'],
        'before',
        ['1.1', '2'],
        FALSE,
        FALSE,
        [
          '' => 'Pick one',
          '1' => 'One (1)',
          '1.1' => '# One.One (11)',
          '1.1.1' => '## One.One.One (111)',
          '1.2' => '# One.Two (12)',
          '1.3' => '# One.Three (13)',
          '1.3.1' => '## One.Three.One (131)',
          '2' => 'Two (2)',
          '2.1' => '# Two.One (21)',
        ],
      ],
    ];
  }

}
