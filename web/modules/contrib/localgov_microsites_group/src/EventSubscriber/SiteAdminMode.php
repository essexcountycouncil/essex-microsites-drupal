<?php

namespace Drupal\localgov_microsites_group\EventSubscriber;

use Drupal\domain\DomainNegotiatorInterface;
use Drupal\group_sites\GroupSitesAdminModeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Fixes admin mode for control domain.
 */
class SiteAdminMode implements EventSubscriberInterface {

  /**
   * Constructs a new SiteAdminMode instance.
   */
  public function __construct(protected DomainNegotiatorInterface $domainNegotiator, protected GroupSitesAdminModeInterface $adminMode) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['fixAdminMode', 100];
    return $events;
  }

  /**
   * Fix admin mode for the control site.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event.
   */
  public function fixAdminMode(RequestEvent $event) {
    if ($this->domainNegotiator->getActiveDomain()?->isDefault()) {
      $this->adminMode->setAdminModeOverride(TRUE);
    }
  }

}
