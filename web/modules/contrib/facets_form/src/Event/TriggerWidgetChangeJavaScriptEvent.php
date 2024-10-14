<?php

declare(strict_types = 1);

namespace Drupal\facets_form\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Subscribe to this event to enable triggering the widget change JS event.
 *
 * When a user interacts with a Facets Form widget a Javascript event can be
 * triggered, allowing 3rd-party to listen and take some action. By default, the
 * JavaScript event is not triggered but modules interested to listen on live
 * widget changes should enable this feature by subscribing to this event. Check
 * the `facets_form_live_total` sub-module for a use-case.
 */
class TriggerWidgetChangeJavaScriptEvent extends Event {

  /**
   * Whether to trigger the widget change JS event.
   *
   * @var bool
   */
  protected $triggerWidgetChangeEvent = FALSE;

  /**
   * The facets source ID.
   *
   * @var string
   */
  protected $facetsSourceId;

  /**
   * The facets form block settings.
   *
   * @var array
   */
  protected $blockSettings;

  /**
   * Constructs a new event instance.
   *
   * @param string $facets_source_id
   *   The facets source ID.
   * @param array $block_settings
   *   The facets form block settings.
   */
  public function __construct(string $facets_source_id, array $block_settings) {
    $this->facetsSourceId = $facets_source_id;
    $this->blockSettings = $block_settings;
  }

  /**
   * Enables triggering the widget change JS event.
   *
   * @return $this
   */
  public function triggerWidgetChangeEvent(): self {
    $this->triggerWidgetChangeEvent = TRUE;
    return $this;
  }

  /**
   * Disables triggering the widget change JS event.
   *
   * @return $this
   */
  public function disableTriggerWidgetChangeEvent(): self {
    $this->triggerWidgetChangeEvent = FALSE;
    return $this;
  }

  /**
   * Checks whether to trigger the widget change JS event.
   *
   * @return bool
   *   Whether to trigger the widget change JS event.
   */
  public function shouldTriggerWidgetChangeEvent(): bool {
    return $this->triggerWidgetChangeEvent;
  }

  /**
   * Returns the facets source ID.
   *
   * @return string
   *   The facets source ID.
   */
  public function getFacetsSourceId(): string {
    return $this->facetsSourceId;
  }

  /**
   * Returns the facets form block settings.
   *
   * @return array
   *   The facets form block settings.
   */
  public function getBlockSettings(): array {
    return $this->blockSettings;
  }

}
