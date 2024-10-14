<?php

declare(strict_types = 1);

namespace Drupal\facets_form_date_range;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\facets\FacetInterface;

/**
 * Represents a date range data model.
 */
class DateRange {

  /**
   * Date type: date only.
   */
  const TYPE_DATE = 'date';

  /**
   * Date type: date and time.
   */
  const TYPE_DATETIME = 'datetime';

  /**
   * Beginning date/time.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime|null
   */
  protected $from;

  /**
   * End date/time.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime|null
   */
  protected $to;

  /**
   * The date type: 'date' or 'datetime'.
   *
   * @var string
   */
  protected $type;

  /**
   * The delimiter used when building the date range string representation.
   *
   * @var string
   */
  protected $delimiter = '~';

  /**
   * Sets the beginning date/time.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime|null $from
   *   Begin date/time.
   *
   * @return $this
   */
  public function setFrom(?DrupalDateTime $from): self {
    $this->from = $from;
    return $this;
  }

  /**
   * Returns the beginning date/time.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   Beginning date/time.
   */
  public function getFrom(): ?DrupalDateTime {
    return $this->from;
  }

  /**
   * Sets the ending date/time.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime|null $to
   *   End date/time.
   *
   * @return $this
   */
  public function setTo(?DrupalDateTime $to): self {
    $this->to = $to;
    return $this;
  }

  /**
   * Returns the ending date/time.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The ending date/time.
   */
  public function getTo(): ?DrupalDateTime {
    return $this->to;
  }

  /**
   * Extracts the date range from a string representing an date interval.
   *
   * @param string $interval
   *   A string with the two dates concatenated using the delimiter. One of the
   *   dates may be missed.
   */
  public function setFromIntervalString(string $interval): void {
    if (strpos($interval, $this->getDelimiter()) === FALSE) {
      throw new \InvalidArgumentException("Not a valid '{$interval}' interval. The '{$this->getDelimiter()}' delimiter is missing.");
    }

    [$from, $to] = explode($this->getDelimiter(), $interval, 2);

    // Guess the date type from passed values.
    $length = strlen(max($from, $to));
    if ($length === 10) {
      // The 'Y-m-d' format.
      $type = self::TYPE_DATE;
    }
    elseif ($length === 25) {
      $type = self::TYPE_DATETIME;
    }
    else {
      throw new \InvalidArgumentException("Malformed '{$interval}' interval. Date should be a 'date only' or 'date and time'.");
    }

    $this
      ->setType($type)
      ->setFrom($from ? new DrupalDateTime($from) : NULL)
      ->setTo($to ? new DrupalDateTime($to) : NULL);
  }

  /**
   * Sets the date type: 'date' or 'datetime'.
   *
   * @param string $type
   *   The date type: 'date' or 'datetime'.
   *
   * @return $this
   */
  public function setType(string $type = self::TYPE_DATE): self {
    assert(in_array($type, [self::TYPE_DATE, self::TYPE_DATETIME], TRUE));
    $this->type = $type;
    return $this;
  }

  /**
   * Gets the date type: 'date' or 'datetime'.
   *
   * @return string
   *   The date type: 'date' or 'datetime'.
   */
  public function getType(): string {
    return $this->type;
  }

  /**
   * Sets the delimiter used when building the date range string representation.
   *
   * @param string $delimiter
   *   The delimiter used when building the date range string representation.
   *
   * @return $this
   */
  public function setDelimiter(string $delimiter): self {
    assert(!empty($delimiter));
    $this->delimiter = $delimiter;
    return $this;
  }

  /**
   * Gets the delimiter used when building the date range string representation.
   *
   * @return string
   *   The delimiter used when building the date range string representation.
   */
  public function getDelimiter(): string {
    return $this->delimiter;
  }

  /**
   * Checks whether the date range is empty.
   *
   * @return bool
   *   The date range is empty when both dates are empty.
   */
  public function isEmpty(): bool {
    return $this->getFrom() === NULL && $this->getTo() === NULL;
  }

  /**
   * Adds time to the beginning date of type 'date'.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The beginning date as date/time.
   */
  public function getFromDateAsDatetime(): ?DrupalDateTime {
    $date = $this->getFrom();
    if ($date && $this->getType() === self::TYPE_DATE) {
      $date->setTime(0, 0);
    }
    return $date;
  }

  /**
   * Adds time to the ending date of type 'date'.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The ending date as date/time.
   */
  public function getToDateAsDatetime(): ?DrupalDateTime {
    $date = $this->getTo();
    if ($date && $this->getType() === self::TYPE_DATE) {
      $date->setTime(23, 59, 59, 999999);
    }
    return $date;
  }

  /**
   * Creates a date range from a given the facet active items.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet.
   *
   * @return \Drupal\facets_form_date_range\DateRange
   *   A date range object.
   */
  public static function createFromFacet(FacetInterface $facet): DateRange {
    $date_range = new static();
    if ($active_items = $facet->getActiveItems()) {
      $date_range->setFromIntervalString($active_items[0]);
    }
    return $date_range;
  }

  /**
   * Returns the beginning date timezone as formatted offset.
   *
   * @return string|null
   *   The beginning date timezone as formatted offset.
   */
  public function getFromTimezone(): ?string {
    return $this->getFrom() ? $this->getFrom()->format('P') : NULL;
  }

  /**
   * Returns the ending date timezone as formatted offset.
   *
   * @return string|null
   *   The ending date timezone as formatted offset.
   */
  public function getToTimezone(): ?string {
    return $this->getTo() ? $this->getTo()->format('P') : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return $this->formatFrom() . $this->getDelimiter() . $this->formatTo();
  }

  /**
   * Formats the beginning date as string.
   *
   * @return string
   *   The beginning date as string.
   */
  protected function formatFrom(): string {
    return $this->format($this->getFrom());
  }

  /**
   * Formats the ending date as string.
   *
   * @return string
   *   The ending date as string.
   */
  protected function formatTo(): string {
    return $this->format($this->getTo());
  }

  /**
   * Formats a date as string.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime|null $date
   *   The date to be formatted as string.
   *
   * @return string
   *   A date formatted as string.
   */
  protected function format(?DrupalDateTime $date): string {
    return $date ? $date->format($this->getType() === self::TYPE_DATE ? 'Y-m-d' : \DateTimeInterface::ATOM) : '';
  }

}
