<?php

namespace Drupal\path_redirect_import;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Alters container services.
 */
class PathRedirectImportServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // If module "migrate_tools" not installed after upgrade from 8.x-1.0
    // module version then disable drush commands and redirect_export before
    // module "migrate_tools" not enabled in update
    // "path_redirect_import_update_9001".
    $modules = $container->getParameter('container.modules');
    if (!isset($modules['migrate_tools'])) {
      $container->removeDefinition('path_redirect_import.commands');
      $container->removeDefinition('path_redirect_import.redirect_export');
    }
  }

}
