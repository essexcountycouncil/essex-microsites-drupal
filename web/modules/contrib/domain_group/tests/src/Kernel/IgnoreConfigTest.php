<?php

namespace Drupal\Tests\domain_group\Kernel;

use Drupal\Core\Config\StorageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Site\Settings;
use Drupal\domain\Entity\Domain;

/**
 * Tests ignoring domain config generated for groups.
 *
 * @group domain_group
 */
class IgnoreConfigTest extends KernelTestBase {

  /**
   * Domain config doesn't have a schema.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'domain_group',
    'domain',
    'domain_config',
    'domain_group_config_ignore_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig([
      'system',
      'domain',
      'domain_group',
      'domain_group_config_ignore_test',
    ]);
  }

  /**
   * Test excluding modules from the config export.
   */
  public function testExcludedConfig(): void {
    // Assert that our facet config is in the active config.
    $active = $this->container->get('config.storage');
    $this->assertNotEmpty($active->listAll('domain.config.'));
    $this->assertNotEmpty($active->listAll('domain.record.'));
    $this->assertNotEmpty($active->listAll('system.'));
    // Add collections.
    $collection = $this->randomMachineName();
    foreach ($active->listAll() as $config) {
      $active->createCollection($collection)->write($config, $active->read($config));
    }

    // Assert that facet config is not in the export storage.
    $export = $this->container->get('config.storage.export');
    assert($export instanceof StorageInterface);
    $this->assertEmpty($export->listAll('domain.config.'));
    $this->assertEmpty($export->listAll('domain.record.'));
    $this->assertNotEmpty($export->listAll('system.'));
    // And assert excluded from collections too.
    $this->assertEmpty($export->createCollection($collection)->listAll('domain.config.'));
    $this->assertEmpty($export->createCollection($collection)->listAll('domain.record.'));
    $this->assertNotEmpty($export->createCollection($collection)->listAll('system.'));

    // Assert that existing facet config is again in the import storage.
    $import = $this->container->get('config.import_transformer')->transform($export);
    assert($import instanceof StorageInterface);
    $this->assertNotEmpty($import->listAll('domain.config.'));
    $this->assertNotEmpty($import->listAll('domain.record.'));
    $this->assertNotEmpty($import->listAll('system.'));

    // Enable export.
    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings['domain_group_export_group_config'] = TRUE;
    new Settings($settings);
    drupal_flush_all_caches();
    // Assert that facet config is in the export storage.
    $export = $this->container->get('config.storage.export');
    assert($export instanceof StorageInterface);
    $this->assertNotEmpty($export->listAll('domain.config.'));
    $this->assertNotEmpty($export->listAll('domain.record.'));
    $this->assertNotEmpty($export->listAll('system.'));
    // And assert excluded from collections too.
    $this->assertNotEmpty($export->createCollection($collection)->listAll('domain.config.'));
    $this->assertNotEmpty($export->createCollection($collection)->listAll('domain.record.'));
    $this->assertNotEmpty($export->createCollection($collection)->listAll('system.'));

    // Assert config not removed if it exists in exported storage.
    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings['domain_group_export_group_config'] = FALSE;
    new Settings($settings);
    drupal_flush_all_caches();
    $sync = $this->container->get('config.storage.sync');
    assert($sync instanceof StorageInterface);
    $active = $this->container->get('config.storage');
    assert($active instanceof StorageInterface);
    // Store sync storage, and make changes to active storage.
    $this->copyConfig($active, $sync);
    $active_domain_group_config = $active->read('domain.config.group_1.system.site');
    $active_domain_group_config['name'] = 'Updated';
    $active->write('domain.config.group_1.system.site', $active_domain_group_config);
    $active_system_config = $active->read('system.site');
    $active_system_config['name'] = 'Updated';
    $active->write('system.site', $active_system_config);
    // Assert that domain config remains as is,
    // but update to sytem is exported.
    $export = $this->container->get('config.storage.export');
    assert($export instanceof StorageInterface);
    $export_facet_type_config = $export->read('domain.config.group_1.system.site');
    $this->assertEquals($export_facet_type_config['name'], 'Microsite 1');
    $export_system_config = $export->read('system.site');
    $this->assertEquals($export_system_config['name'], 'Updated');

    // Assert config is imported if present.
    $import = $this->container->get('config.import_transformer')->transform($export);
    assert($export instanceof StorageInterface);
    $import_facet_type_config = $import->read('domain.config.group_1.system.site');
    $this->assertEquals($import_facet_type_config['name'], 'Microsite 1');

    // Assert another domain is exported.
    $domain = Domain::create([
      'status' => TRUE,
      'id' => 'different',
      'hostname' => 'other.localhost',
      'scheme' => 'variable',
      'is_default' => FALSE,
    ]);
    $domain->save();
    $export = $this->container->get('config.storage.export');
    assert($export instanceof StorageInterface);
    $this->assertCount(1, $export->listAll('domain.record.'));
  }

}
