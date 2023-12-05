<?php

namespace Drupal\localgov_microsites_group\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Group redirects.
 */
class GroupContentRedirectSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['redirectGroupContent'];
    return $events;
  }

  /**
   * Redirect hidden group content page.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event.
   */
  public function redirectGroupContent(RequestEvent $event) {

    $request = $event->getRequest();
    $path = $request->getPathInfo();
    if (preg_match('/^\/group\/\d+\/content$/', $path)) {
      $event->setResponse(new RedirectResponse($path . '/../nodes'));
    }
  }

}
