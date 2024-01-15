<?php

namespace Drupal\ecc_waste\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a postcode search block.
 *
 * @Block(
 *   id = "ecc_waste_postcode_search",
 *   admin_label = @Translation("Postcode search"),
 *   category = @Translation("Custom")
 * )
 */
class PostcodeSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['content'] = \Drupal::formBuilder()->getForm('Drupal\ecc_waste\Form\PostcodeForm');
    return $build;
  }

}
