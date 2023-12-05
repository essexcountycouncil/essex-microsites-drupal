<?php

namespace Drupal\group_content_menu;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the parent form selector service.
 */
class GroupContentMenuServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($definition = $container->getDefinition('menu.parent_form_selector')) {
      $definition->setClass(GroupContentMenuParentFormSelector::class);
    }
  }

}
