<?php

namespace Drupal\domain_group_alias;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Alter the service container to use a custom class.
 */
class DomainGroupAliasServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('domain_path.helper');

    // Use DomainGroupAliasHelper class instead of the
    // default DomainPathHelper class.
    $definition->setClass('Drupal\domain_group_alias\DomainGroupAliasHelper');
  }

}
