<?php

namespace Drupal\Tests\path_redirect_import\Kernel\Plugin\migrate\source;

use Drupal\path_redirect_import_test\Form\MigrateRedirectForm;
use Drupal\redirect\Entity\Redirect;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * @coversDefaultClass \Drupal\path_redirect_import\Plugin\migrate\source\CSVRedirect
 *
 * @group path_redirect_import
 *
 * @requires module redirect
 */
class CSVRedirectTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'file',
    'link',
    'user',
    'menu_link_content',
    'redirect',
    'path_alias',
    'migrate',
    'migrate_source_csv',
    'path_redirect_import',
    'path_redirect_import_test',
  ];

  /**
   * Tests execution of a redirect migration sourced from CSV.
   */
  public function testMigrate(): void {
    $this->installEntitySchema('redirect');
    $this->installConfig(['path_redirect_import', 'path_redirect_import_test']);

    /** @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migrationManager */
    $migrationManager = $this->container->get('plugin.manager.migration');
    $migration = $migrationManager->createInstance('path_redirect_import');
    $this->executeMigration($migration);

    $this->assertRedirect(1, 'und', [
      'query' => [],
      'path' => 'source-path',
    ], 'internal:/', '301');
    $this->assertRedirect(2, 'en', [
      'query' => ['param' => 'value'],
      'path' => 'source-path-other',
    ], 'base:my-path', '302');
    $this->assertRedirect(3, 'und', [
      'query' => [],
      'path' => 'my-source-path',
    ], 'https://example.com', '304');
    $this->assertRedirect(4, 'und', [
      'query' => [],
      'path' => 'path with spaces',
    ], 'base:new space path', '301');
  }

  /**
   * Tests whether redirects to delete are detected properly.
   *
   * @param string $path
   *   The CSV file to test path.
   * @param int $count
   *   The expected number of redirects to delete.
   *
   * @dataProvider providerTestRedirectDeleteCount
   */
  public function testDeleteDetection(string $path, int $count): void {
    $path = \Drupal::service('extension.list.module')->getPath('path_redirect_import_test') . $path;
    $this->installEntitySchema('redirect');
    $this->installConfig(['path_redirect_import', 'path_redirect_import_test']);

    /** @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migrationManager */
    $migrationManager = $this->container->get('plugin.manager.migration');
    $migration = $migrationManager->createInstance('path_redirect_import');
    $this->executeMigration($migration);

    $form = MigrateRedirectForm::create($this->container);
    $form->setReaderPath($path);
    $redirectsToDelete = $form->redirectsToDeletePublic();
    $this->assertEquals($count, count($redirectsToDelete));
  }

  /**
   * Provides test cases for ::testDeleteDetection().
   *
   * @return array
   *   Test cases for ::testDrupalStaticResetDeprecation().
   */
  public function providerTestRedirectDeleteCount(): array {
    return [
      ['/artifacts/redirect_2.csv', 2],
      ['/artifacts/redirect.csv', 4],
    ];
  }

  /**
   * Asserts a Redirect entity values.
   *
   * @param int $redirectId
   *   The redirect ID to assert.
   * @param string $langcode
   *   The redirect expected langcode.
   * @param array $source
   *   The redirect expected source.
   * @param string $destination
   *   The redirect expected destination.
   * @param string $statusCode
   *   The redirect expected status code.
   */
  protected function assertRedirect(int $redirectId, string $langcode, array $source, string $destination, string $statusCode): void {
    $redirect = Redirect::load($redirectId);
    $this->assertEquals($redirect->language()->getId(), $langcode);
    $this->assertEquals($redirect->getSource(), $source);
    $this->assertEquals($redirect->getRedirect()['uri'], $destination);
    $this->assertEquals($redirect->getStatusCode(), $statusCode);
  }

}
