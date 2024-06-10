<?php

namespace Drupal\domain_group\EventSubscriber;

use Drupal\domain\DomainNegotiatorInterface;
use Drupal\domain\DomainRedirectResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Event subscriber subscribing to KernelEvents::REQUEST.
 */
class LoginPageRedirect implements EventSubscriberInterface {

  /**
   * The domain negotiator service.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Domain storage handler service.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The domain_group.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a LoginPageRedirect object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(DomainNegotiatorInterface $negotiator, EntityTypeManagerInterface $entity_type_manager, AccountInterface $account, RouteMatchInterface $route_match, ConfigFactoryInterface $config_factory) {
    $this->domainNegotiator = $negotiator;
    $this->entityTypeManager = $entity_type_manager;
    $this->domainStorage = $this->entityTypeManager->getStorage('domain');
    $this->account = $account;
    $this->routeMatch = $route_match;
    $this->config = $config_factory->get('domain_group.settings');
  }

  /**
   * Redirects user.login route to the Default Domain.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The RequestEvent to process.
   */
  public function loginRedirect(RequestEvent $event) {
    // Check if login is restricted to Default domain in the module config.
    if ($this->config->get('restricted_login')) {
      /** @var \Drupal\domain\DomainInterface $default_domain */
      $default_domain = $this->domainStorage->loadDefaultDomain();
      // Only redirect if there is a default domain.
      if (!$default_domain) {
        return;
      }
      // Get Url and hostname from Default Domain.
      $domain_hostname = $default_domain->getHostname();
      $domain_url = $default_domain->getUrl();
      $current_host = $event->getRequest()->getHost();
      $is_default = FALSE;

      // Only return a response for a master request.
      if (!$event->isMasterRequest()) {
        return;
      }

      // Check if current hostname belongs to a Domain Entity.
      if ($current_domain = $this->domainStorage->loadByHostname($current_host)) {
        // Check if current Domain is the Default.
        $is_default = $current_domain->isDefault();
      }
      // If Anon user requests the login page from a different host, redirect.
      if (!$is_default && $this->account->isAnonymous() && $this->routeMatch->getRouteName() == 'user.login') {
        if (DomainRedirectResponse::checkTrustedHost($domain_hostname)) {
          $response = new TrustedRedirectResponse($domain_url, 301);
        }
        else {
          // If the redirect is not a registered hostname, reject the request.
          $response = new Response('The provided host name is not a valid redirect.', 401);
        }
        $event->setResponse($response);
        $event->stopPropagation();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['loginRedirect'];
    return $events;
  }

}
