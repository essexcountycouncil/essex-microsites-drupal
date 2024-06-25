<?php

namespace Drupal\sortableviews\Plugin\views\area;

use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\Component\Utility\Html;

/**
 * A container where the save button will appear.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("save_sortable_changes")
 */
class SaveSortableViewsChanges extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty) {
      return [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => '',
        '#attributes' => [
          'class' => ['sortableviews-save-changes'],
          'id' => Html::getUniqueId('sortableviews-save-changes'),
        ],
      ];
    }
    return [];
  }

}
