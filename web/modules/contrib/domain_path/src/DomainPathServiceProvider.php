<?php

namespace Drupal\domain_path;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Overrides the path_alias.manager service.
 * Wrap it with our DomainPathAliasManager.
 */
class DomainPathServiceProvider extends ServiceProviderBase implements ServiceModifierInterface {
  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('path_alias.manager');
    $definition->setClass('Drupal\domain_path\DomainPathAliasManager');
  }

}
