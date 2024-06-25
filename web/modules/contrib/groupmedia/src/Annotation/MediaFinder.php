<?php

namespace Drupal\groupmedia\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a media_finder annotation object.
 *
 * @see hook_media_finder_info_alter()
 *
 * @Annotation
 */
class MediaFinder extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the tracking method.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the tracking method.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * The field types that this plugin is able to track.
   *
   * @var string[]
   */
  public $field_types = [];

  /**
   * The element to look for.
   *
   * @var string
   */
  public $element = '';

}
