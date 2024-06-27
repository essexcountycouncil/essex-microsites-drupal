<?php

namespace Drupal\path_redirect_import\Form;

use Drupal\Component\Render\MarkupInterface;

/**
 * Provides the CSV sample data table for the different forms.
 */
trait SampleCsvFormTrait {

  /**
   * Provides the CSV Sample data table.
   *
   * @param \Drupal\Component\Render\MarkupInterface $caption
   *   The caption to show above the table.
   *
   * @return array
   *   The sample data table render array.
   */
  protected function getSampleCsvTable(MarkupInterface $caption) {
    $sample = [
      [
        'source' => 'source-path',
        'destination' => '<front>',
        'language' => 'und',
        'status_code' => 301,
      ],
      [
        'source' => 'source-path-other?param=value',
        'destination' => 'my-path',
        'language' => 'en',
        'status_code' => 302,
      ],
      [
        'source' => 'my-source-path',
        'destination' => 'https://example.com',
        'language' => 'und',
        'status_code' => 302,
      ],
    ];

    // Define the table headers.
    $headers = [
      'source' => 'Source',
      'destination' => 'Destination',
      'language' => 'Language',
      'status_code' => 'Status Code',
    ];

    // Initialize the table rows.
    $rows = [];

    foreach ($sample as $data) {
      $rows[] = [
        'source' => $data['source'],
        'destination' => $data['destination'],
        'language' => $data['language'],
        'status_code' => $data['status_code'],
      ];
    }

    // Create the table element.
    return [
      '#theme' => 'table',
      '#caption' => $caption,
      '#header' => $headers,
      '#rows' => $rows,
    ];
  }

}
