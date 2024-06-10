<?php

namespace Drupal\localgov_microsites_events\EventSubscriber;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\group_context_domain\GroupFromDomainContextTrait;
use Drupal\localgov_microsites_group\ContentTypeHelperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to hide events listing view if events aren't enabled.
 */
class EventsListingCheckEventSubscriber implements EventSubscriberInterface {

  use GroupFromDomainContextTrait;

  /**
   * The group permissions helper.
   *
   * @var \Drupal\localgov_microsites_group\ContentTypeHelperInterface
   */
  protected $contentTypeHelper;

  /**
   * Returns an EventsListingCheckEventSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository interface.
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   The domain negotiator interface.
   * @param \Drupal\localgov_microsites_group\ContentTypeHelperInterface $content_type_helper
   *   The group content type helper.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, DomainNegotiatorInterface $domain_negotiator, ContentTypeHelperInterface $content_type_helper) {
    $this->entityRepository = $entity_repository;
    $this->domainNegotiator = $domain_negotiator;
    $this->contentTypeHelper = $content_type_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[KernelEvents::REQUEST][] = ['checkEventsListingAccess'];
    return $events;
  }

  /**
   * Check if the events listing page should be displayed.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function checkEventsListingAccess(RequestEvent $event) {

    // Check we're on an events listing page.
    if (
      $event->getRequest()->getPathInfo() != '/events' &&
      $event->getRequest()->getPathInfo() != '/events/search'
    ) {
      return;
    }

    // Don't process events with HTTP exceptions.
    if (!is_null($event->getRequest()->get('exception'))) {
      return;
    }

    $group = $this->getGroupFromDomain();
    if (!$group) {
      return;
    }

    // If events aren't enabled return a 404.
    if ($this->contentTypeHelper->moduleStatus('localgov_microsites_events', $group) != $this->contentTypeHelper::ENABLED) {
      throw new NotFoundHttpException();
    }
  }

}
