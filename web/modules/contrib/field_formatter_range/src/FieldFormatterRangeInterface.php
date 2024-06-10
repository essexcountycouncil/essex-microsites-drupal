<?php

declare(strict_types = 1);

namespace Drupal\field_formatter_range;

/**
 * Field Formatter Range interface to define constants.
 */
interface FieldFormatterRangeInterface {

  /**
   * Order type default.
   */
  public const ORDER_DEFAULT = 0;

  /**
   * Order type reverse.
   */
  public const ORDER_REVERSE = 1;

  /**
   * Order type random.
   */
  public const ORDER_RANDOM = 2;

}
