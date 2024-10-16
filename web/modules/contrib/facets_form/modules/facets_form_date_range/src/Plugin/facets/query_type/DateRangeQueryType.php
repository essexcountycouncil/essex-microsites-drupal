<?php

declare(strict_types = 1);

namespace Drupal\facets_form_date_range\Plugin\facets\query_type;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\QueryType\QueryTypePluginBase;
use Drupal\facets\Result\Result;
use Drupal\facets_form_date_range\DateRange;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Query type plugin that filters the result by the date active filters.
 *
 * @FacetsQueryType(
 *   id = "facets_form_date_range_query_type",
 *   label = @Translation("Date query type (Facets Form)"),
 * )
 */
class DateRangeQueryType extends QueryTypePluginBase implements ContainerFactoryPluginInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new facet plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatterInterface $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if (empty($this->query)) {
      return;
    }

    $date_range = DateRange::createFromFacet($this->facet);

    // No date range.
    if ($date_range->isEmpty()) {
      return;
    }

    $operator = $this->getOperator($date_range);
    if ($operator === 'BETWEEN') {
      $value = [
        $date_range->getFromDateAsDatetime()->getTimestamp(),
        $date_range->getToDateAsDatetime()->getTimestamp(),
      ];
    }
    elseif ($operator === '>=') {
      $value = $date_range->getFromDateAsDatetime()->getTimestamp();
    }
    else {
      $value = $date_range->getToDateAsDatetime()->getTimestamp();
    }

    $this->query->addCondition($this->facet->getFieldIdentifier(), $value, $operator);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [];

    $date_range = DateRange::createFromFacet($this->facet);
    if ($date_range->isEmpty()) {
      return $build;
    }

    $operator = $this->getOperator($date_range);

    if ($operator === 'BETWEEN') {
      $display = $this->t('Between @from and @to', [
        '@from' => $this->formatDate($date_range->getFrom()),
        '@to' => $this->formatDate($date_range->getTo()),
      ]);
    }
    elseif ($operator === '>=') {
      $display = $this->t('After @from', [
        '@from' => $this->formatDate($date_range->getFrom()),
      ]);
    }
    else {
      $display = $this->t('Before @to', [
        '@to' => $this->formatDate($date_range->getTo()),
      ]);
    }

    $build[] = new Result($this->facet, (string) $date_range, $display, 0);
    $this->facet->setResults($build);

    return $build;
  }

  /**
   * Computes the operator given a date range.
   *
   * @param \Drupal\facets_form_date_range\DateRange $date_range
   *   The date range.
   *
   * @return string
   *   The operator: '>=', '<=' or 'BETWEEN'.
   *
   * @throws \InvalidArgumentException
   *   When passed date range lacks both, beginning and ending dates.
   */
  protected function getOperator(DateRange $date_range): string {
    if ($date_range->isEmpty()) {
      throw new \InvalidArgumentException('Passed date range should have at least one of the beginning or ending dates set.');
    }

    if ($date_range->getFrom()) {
      $operator = '>=';
      if ($date_range->getTo()) {
        $operator = 'BETWEEN';
      }
      return $operator;
    }
    return '<=';
  }

  /**
   * Returns a formatted date/time according to widget configuration.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The date/time being formatted.
   *
   * @return string
   *   The formatted date/time.
   */
  protected function formatDate(DrupalDateTime $date): string {
    $date_format = $this->facet->getWidgetInstance()->getConfiguration()['date_format'];
    return $this->dateFormatter->format($date->getTimestamp(), $date_format['type'], $date_format['custom']);
  }

}
