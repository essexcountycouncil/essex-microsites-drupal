<?php

declare(strict_types = 1);

namespace Drupal\Tests\facets_form\Kernel\Plugin\facets\widget;

use Drupal\facets\Entity\Facet;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\facets_form\Traits\FacetsFormWidgetTestTrait;

/**
 * @coversDefaultClass \Drupal\facets_form\Plugin\facets\widget\CheckboxWidget
 * @group facets_form
 */
class CheckboxWidgetTest extends KernelTestBase {

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
   * Test the Facets Form checkbox widget build.
   *
   * @param array $facet_values
   *   Facet entity values.
   * @param array $widget_config
   *   The widget configuration.
   * @param array $data
   *   The used to build the results.
   * @param array $active_items
   *   Active items.
   * @param string|null $expected_title
   *   Widget '#title' expectation.
   * @param array $expected_default_values
   *   Widget '#default_value' expectation.
   * @param bool $expected_disabled
   *   Widget '#disabled' expectation.
   * @param array $expected_options
   *   Widget options expectation.
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
    ?string $expected_title,
    array $expected_default_values,
    bool $expected_disabled,
    array $expected_options
  ): void {
    $facet = new Facet(['id' => 'foo'] + $facet_values, 'facets_facet');
    $facet->setFacetSourceId('facets_form_test');
    $facet->setWidget('facets_form_checkbox', $widget_config);
    $facet->setResults($this->getResults($facet, $data, $active_items));

    $build = $facet->getWidgetInstance()->build($facet)['foo'];
    $this->assertSame('fieldset', $build['#type']);
    $this->assertCount(count($data, COUNT_RECURSIVE), $build[$facet->id()]['#options']);
    $this->assertEquals($expected_title, $build['#title']);
    $this->assertEquals($expected_default_values, $build[$facet->id()]['#default_value']);
    $this->assertSame($expected_disabled, $build[$facet->id()]['#disabled']);
    foreach ($build[$facet->id()]['#options'] as $value => $label) {
      $this->assertEquals($expected_options[$value], $label);
    }
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
          'name' => 'Baz',
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
        'Baz',
        ['1.1', '2'],
        FALSE,
        [
          '1' => 'One',
          '1.1' => 'One.One',
          '1.1.1' => 'One.One.One',
          '1.2' => 'One.Two',
          '1.3' => 'One.Three',
          '1.3.1' => 'One.Three.One',
          '2' => 'Two',
          '2.1' => 'Two.One',
        ],
      ],
      // @todo Add a radios test case in #3227076.
      // @see https://www.drupal.org/project/facets_form/issues/3227076
      // 'show_only_one_result' => [],
      'empty_items' => [
        [
          'name' => 'Baz',
          'show_title' => FALSE,
        ],
        [
          'disabled_on_empty' => TRUE,
        ],
        [],
        [],
        NULL,
        [],
        TRUE,
        [],
      ],
      'with_show_number' => [
        [
          'name' => 'Baz',
          'show_title' => TRUE,
        ],
        [
          'show_numbers' => TRUE,
        ],
        [
          '1' => [],
          '2' => [],
        ],
        ['1'],
        'Baz',
        ['1'],
        FALSE,
        [
          '1' => 'One (1)',
          '2' => 'Two (2)',
        ],
      ],
    ];
  }

}
