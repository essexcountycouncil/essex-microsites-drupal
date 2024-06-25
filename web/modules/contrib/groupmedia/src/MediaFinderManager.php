<?php

namespace Drupal\groupmedia;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages entity_usage track plugins.
 */
class MediaFinderManager extends DefaultPluginManager {

  /**
   * Constructs a new EntityUsageTrackManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/MediaFinder', $namespaces, $module_handler, 'Drupal\groupmedia\MediaFinderInterface', 'Drupal\groupmedia\Annotation\MediaFinder');
    $this->alterInfo('media_finder_info');
    $this->setCacheBackend($cache_backend, 'media_finder_plugins');
  }

}
