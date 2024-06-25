<?php

namespace Drupal\domain_group\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Domain group settings item annotation object.
 *
 * @see \Drupal\domain_group\Plugin\DomainGroupSettingsManager
 * @see plugin_api
 *
 * @Annotation
 */
class DomainGroupSettings extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
