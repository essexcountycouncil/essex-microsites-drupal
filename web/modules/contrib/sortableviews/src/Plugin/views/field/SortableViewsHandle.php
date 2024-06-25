<?php

namespace Drupal\sortableviews\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Renders a draggable handle.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("sortable_views_handle")
 */
class SortableViewsHandle extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $values->_entity;
    $weightField = $this->view->getStyle()->options['weight_field'];
    $access = $entity->access('update')
      && $entity->get($weightField)->access('update');

    if (!$access) {
      return [];
    }

    return [
      '#theme' => 'sortableviews_handle',
      '#dataid' => $entity->id(),
    ];
  }

}
