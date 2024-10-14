<?php

declare(strict_types = 1);

namespace Drupal\Tests\facets_form\Traits;

use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Drupal\facets\Result\Result;

/**
 * Widget tests reusable code.
 */
trait FacetsFormWidgetTestTrait {

  /**
   * Builds a list of deep nested results.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet.
   * @param array $data
   *   Result data.
   * @param array $active
   *   Active items.
   *
   * @return \Drupal\facets\Result\ResultInterface[]
   *   A list of nested results.
   */
  protected function getResults(FacetInterface $facet, array $data, array $active = []): array {
    $results = [];
    foreach ($data as $value => $children) {
      $display_value = str_replace(['1', '2', '3'], ['One', 'Two', 'Three'], (string) $value);
      $count = (int) str_replace('.', '', (string) $value);
      $result = new Result($facet, $value, $display_value, $count);
      $result->setUrl(Url::fromUri("http://example.com/{$value}"));
      if (in_array($value, $active)) {
        $result->setActiveState(TRUE);
      }
      if (!empty($children)) {
        $result->setChildren($this->getResults($facet, $children, $active));
      }
      $results[] = $result;
    }
    return $results;
  }

}
