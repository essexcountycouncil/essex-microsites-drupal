<?php

namespace Drupal\sortableviews\Plugin\views\style;

use Drupal\views\Plugin\views\style\Table;
use Drupal\sortableviews\SortableViewsStyleTrait;

/**
 * Style plugin to render each item as a row in a table.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "sortable_table",
 *   title = @Translation("Sortable table"),
 *   help = @Translation("Displays sortable rows in a table."),
 *   theme = "views_view_table",
 *   display_types = {"normal"}
 * )
 */
class SortableTable extends Table {

  use SortableViewsStyleTrait;

  /**
   * {@inheritdoc}
   */
  protected function javascriptSelector() {
    return 'tbody';
  }

}
