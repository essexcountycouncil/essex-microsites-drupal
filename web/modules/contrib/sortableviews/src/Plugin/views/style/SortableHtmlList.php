<?php

namespace Drupal\sortableviews\Plugin\views\style;

use Drupal\views\Plugin\views\style\HtmlList;
use Drupal\sortableviews\SortableViewsStyleTrait;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "sortable_html_list",
 *   title = @Translation("Sortable HTML List"),
 *   help = @Translation("Displays rows as a sortable HTML list."),
 *   theme = "views_view_list",
 *   display_types = {"normal"}
 * )
 */
class SortableHtmlList extends HtmlList {

  use SortableViewsStyleTrait;

  /**
   * {@inheritdoc}
   */
  protected function javascriptSelector() {
    return 'ul, ol';
  }

}
