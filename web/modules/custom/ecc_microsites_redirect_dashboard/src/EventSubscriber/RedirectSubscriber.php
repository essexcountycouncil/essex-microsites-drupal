<?php
namespace Drupal\ecc_microsites_redirect_dashboard\EventSubscriber;

use Drupal\group\GroupMembershipLoaderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RedirectSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => 'redirectGroupNodes',
    ];
  }

  protected $membershipLoader;

  public function __construct($membership_loader) {
    $this->membershipLoader = $membership_loader;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('group.membership_loader')
    );
  }

  public function redirectGroupNodes(RequestEvent $event) {
    $request = $event->getRequest();
    // Check if the current route is 'entity.group.canonical'
    if ($request->attributes->get('_route') === 'entity.group.canonical') {
      $group = $request->attributes->get('group');
      if ($group) {
        $group_id = $group->id();
        $current_user = \Drupal::currentUser();
        // Check if current user is logged in
        if ($current_user->id() > 0) {
          // Check the group role of the current member in this group.
          // 1. If they are admins, they should stay on the group page.
          // 2. If they are members, they should be redirected to the nodes page.
          foreach ($this->membershipLoader->loadByUser($current_user) as $group_membership) {
            if (array_key_exists('microsite-member', $group_membership->getRoles()) && !array_key_exists('microsite-admin', $group_membership->getRoles())) {
              $response = new RedirectResponse($group_id . '/nodes');
              $event->setResponse($response);
            }
          }
        }
      }
    }
  }
}
