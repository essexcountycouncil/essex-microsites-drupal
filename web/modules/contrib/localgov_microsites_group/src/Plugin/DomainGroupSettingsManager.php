<?php

namespace Drupal\localgov_microsites_group\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Domain group settings plugin manager.
 */
class DomainGroupSettingsManager extends DefaultPluginManager {

  /**
   * A collection of vanilla instances of all domain group settings plugins.
   *
   * @var \Drupal\localgov_microsites_group\Plugin\DomainGroupSettingsCollection
   */
  protected $allPlugins;

  /**
   * Constructs a new DomainGroupSettingsManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/DomainGroupSettings', $namespaces, $module_handler, 'Drupal\localgov_microsites_group\Plugin\DomainGroupSettingsInterface', 'Drupal\localgov_microsites_group\Annotation\DomainGroupSettings');

    $this->alterInfo('domain_group_domain_group_settings_info');
    $this->setCacheBackend($cache_backend, 'localgov_microsites_group_domain_group_settings_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    if (!isset($this->allPlugins)) {
      $collection = new DomainGroupSettingsCollection($this, []);

      // Add every known plugin to the collection with a vanilla configuration.
      foreach ($this->getDefinitions() as $plugin_id => $plugin_info) {
        $collection->setInstanceConfiguration($plugin_id, ['id' => $plugin_id]);
      }

      // Sort and set the plugin collection.
      $this->allPlugins = $collection->sort();
    }

    return $this->allPlugins;
  }

}
