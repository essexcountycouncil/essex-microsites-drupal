<?php

namespace Drupal\sortableviews\Plugin\views\style;

use Drupal\views\Plugin\views\style\DefaultStyle;
use Drupal\sortableviews\SortableViewsStyleTrait;

/**
 * Style plugin to render rows one after another with no decorations.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "sortable_default",
 *   title = @Translation("Sortable Unformatted list"),
 *   help = @Translation("Displays sortable rows one after another."),
 *   theme = "views_view_unformatted",
 *   display_types = {"normal"}
 * )
 */
class SortableDefault extends DefaultStyle {

  use SortableViewsStyleTrait;

}
