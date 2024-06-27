<?php

namespace Drupal\path_redirect_import;

/**
 * Trait MigratePluginTrait to reuse common migrate config.
 */
trait MigratePluginTrait {

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Helper function to get the migration plugin.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface
   *   The migration plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function migrationPlugin() {
    return $this->migrationPluginManager->createInstance('path_redirect_import');
  }

}
