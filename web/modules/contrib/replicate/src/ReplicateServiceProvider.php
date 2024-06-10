<?php

namespace Drupal\replicate;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Service Provider for Replicate.
 */
class ReplicateServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // When layout_builder is not enabled, remove the layout_builder event
    // subscriber.
    $modules = $container->getParameter('container.modules');
    if (!isset($modules['layout_builder']) && $container->hasDefinition('replicate.event_subscriber.layout_builder')) {
      $container->removeDefinition('replicate.event_subscriber.layout_builder');
    }
  }

}
