<?php

declare(strict_types = 1);

namespace Drupal\facets_form_live_total\EventSubscriber;

use Drupal\facets_form\Event\TriggerWidgetChangeJavaScriptEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Enables the Facets Form JS event dispatching.
 */
class EnableLiveTotalSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      TriggerWidgetChangeJavaScriptEvent::class => 'enableLiveTotal',
    ];
  }

  /**
   * Enables the live total, if configured so.
   *
   * @param \Drupal\facets_form\Event\TriggerWidgetChangeJavaScriptEvent $event
   *   The event object.
   */
  public function enableLiveTotal(TriggerWidgetChangeJavaScriptEvent $event): void {
    if (!empty($event->getBlockSettings()['live_total'])) {
      $event->triggerWidgetChangeEvent();
    }
  }

}
