<?php

declare(strict_types = 1);

namespace Drupal\Tests\facets_form_date_range\Kernel\Plugin\facets\widget;

use Drupal\facets\Entity\Facet;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\facets_form\Traits\FacetsFormWidgetTestTrait;

/**
 * @coversDefaultClass \Drupal\facets_form_date_range\Plugin\facets\widget\DateRangeWidget
 * @group facets_form
 */
class DateRangeWidgetTest extends KernelTestBase {

  use FacetsFormWidgetTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'facets',
    'facets_form',
    'facets_form_date_range',
  ];

  /**
   * Tests the Facets Form date range widget build.
   *
   * @param array $widget_config
   *   The widget configuration.
   * @param string $from_label
   *   The "from" expected label.
   * @param string $to_label
   *   The "to" expected label.
   * @param string $date_time_element
   *   The expected time element config.
   *
   * @dataProvider providerTestPlugin
   *
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   */
  public function testPlugin(
    array $widget_config,
    string $from_label,
    string $to_label,
    string $date_time_element
  ): void {
    $facet = new Facet(['id' => 'foo'], 'facets_facet');
    $facet->setWidget('facets_form_date_range', $widget_config);

    $build = $facet->getWidgetInstance()->build($facet)['foo'];
    $this->assertSame('fieldset', $build['#type']);
    $this->assertSame('datetime', $build['from']['#type']);
    $this->assertEquals($from_label, $build['from']['#title']);
    $this->assertSame('date', $build['from']['#date_date_element']);
    $this->assertSame($date_time_element, $build['from']['#date_time_element']);
    $this->assertSame('datetime', $build['to']['#type']);
    $this->assertEquals($to_label, $build['to']['#title']);
    $this->assertSame('date', $build['to']['#date_date_element']);
    $this->assertSame($date_time_element, $build['to']['#date_time_element']);
  }

  /**
   * Provides test cases for ::testPlugin().
   *
   * @return array[]
   *   Test cases.
   */
  public function providerTestPlugin(): array {
    return [
      'date_granularity' => [
        [
          'date_type' => 'date',
        ],
        'From',
        'To',
        'none',
      ],
      'datetime_granularity' => [
        [
          'date_type' => 'datetime',
          'label' => [
            'from' => 'Start',
            'to' => 'End',
          ],
        ],
        'Start',
        'End',
        'time',
      ],
    ];
  }

}
