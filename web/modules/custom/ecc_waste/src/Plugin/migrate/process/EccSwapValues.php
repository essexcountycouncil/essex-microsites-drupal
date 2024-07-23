<?php

namespace Drupal\ecc_waste\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to customize the location field.
 *
 * @MigrateProcessPlugin(
 *   id = "ecc_swap_values"
 * )
 */
class EccSwapValues extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    // Split the value into A and B.
    [$a, $b] = explode(',', $value);

    // Concatenate the elements.
    $result = $b . ',' . $a;

    return $result;
  }

}
