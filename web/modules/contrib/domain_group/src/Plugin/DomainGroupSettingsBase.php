<?php

namespace Drupal\domain_group\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Domain group settings plugins.
 */
abstract class DomainGroupSettingsBase extends PluginBase implements DomainGroupSettingsInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->pluginDefinition['provider'];
  }

}
