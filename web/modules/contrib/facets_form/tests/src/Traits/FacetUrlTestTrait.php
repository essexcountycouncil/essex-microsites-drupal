<?php

declare(strict_types=1);

namespace Drupal\Tests\facets_form\Traits;

use Symfony\Component\Yaml\Yaml;

/**
 * Helper methods to test urls with facet filters.
 */
trait FacetUrlTestTrait {

  /**
   * Asserts that the current URL matches the expected one, including the query.
   *
   * We use this instead of \Behat\Mink\WebAssert::addressEquals() because:
   * - In Drupal < 9.3, WebAssert::addressEquals() ignores the query string.
   *   This would make most of the assertions useless.
   *   See https://www.drupal.org/node/3164686.
   * - Since Drupal 9.3, WebAssert::addressEquals() ignores the query string, if
   *   the expected url does not have one.
   * - The diff output from WebAssert::addressEquals() is harder to read than
   *   ours, because the url is a single line and contains noise from urlencode.
   * - Low-level string operations are more predictable than \Drupal\core\Url.
   *
   * @param string $path
   *   The expected url path.
   *   If the path contains a query part, it will be merged with $query.
   * @param array $query
   *   Expected url query.
   *
   * @see \Behat\Mink\WebAssert::addressEquals()
   */
  protected function assertCurrentUrl(string $path, array $query = []): void {
    $current_uri = str_replace(
      $this->baseUrl . '/',
      '',
      $this->getSession()->getCurrentUrl(),
    );
    $this->assertSame(
      $this->formatUrlForDiff($path, $query),
      $this->formatUrlForDiff($current_uri),
    );
  }

  /**
   * Produces a diff-friendly string from a url.
   *
   * @param string $uri
   *   Uri or path.
   *   If the uri contains a query part, it will be merged with $query.
   * @param array $query
   *   (optional) Url query parameters.
   *
   * @return string
   *   A diff-friendly string.
   */
  protected function formatUrlForDiff(string $uri, array $query = []): string {
    $parts = parse_url($uri);
    if (isset($parts['query'])) {
      parse_str($parts['query'], $uri_query);
      // Merge query from $uri with the query from $query.
      $query = $uri_query + $query;
    }
    if ($query) {
      // Use array query to get a multi-line diff.
      $parts['query'] = $query;
    }
    // Clean internal paths.
    if (isset($parts['path'])) {
      $parts['path'] = str_replace(
        [
          $this->baseUrl . '/',
          base_path(),
        ],
        '',
        $parts['path'],
      );
    }

    // Use yaml to make the diff more readable.
    return Yaml::dump($parts, 10, 2);
  }

}
