<?php

declare(strict_types = 1);

namespace Drupal\facets_form_test;

use Drupal\facets_form\Event\TriggerWidgetChangeJavaScriptEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test subscriber.
 */
class FacetsFormsTestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      TriggerWidgetChangeJavaScriptEvent::class => 'enable',
    ];
  }

  /**
   * Enables the Javascript event dispatching.
   *
   * @param \Drupal\facets_form\Event\TriggerWidgetChangeJavaScriptEvent $event
   *   The event.
   */
  public function enable(TriggerWidgetChangeJavaScriptEvent $event): void {
    $event->triggerWidgetChangeEvent();
  }

}
