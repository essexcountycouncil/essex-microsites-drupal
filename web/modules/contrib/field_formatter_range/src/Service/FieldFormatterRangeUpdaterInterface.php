<?php

declare(strict_types = 1);

namespace Drupal\field_formatter_range\Service;

/**
 * Field Formatter Range updater interface methods.
 */
interface FieldFormatterRangeUpdaterInterface {

  /**
   * Implementation of field_formatter_range_update_9101().
   */
  public function update9101(): void;

  /**
   * Prepare new settings structure.
   *
   * @param array $fieldFormatterRangeSettings
   *   The field formatter settings with the old structure.
   *
   * @return array
   *   The updated settings.
   */
  public static function update9101PrepareNewSettings(array $fieldFormatterRangeSettings): array;

}
