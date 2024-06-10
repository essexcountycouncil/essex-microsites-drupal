<?php

namespace Drupal\localgov_microsites_group\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\domain_group\DomainGroupResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Redirect to current group admin page.
 */
class MicrositeAdminController extends ControllerBase {

  /**
   * The Domain Group resolver.
   *
   * @var \Drupal\domain_group\DomainGroupResolverInterface
   */
  protected $domainGroupResolver;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('domain_group_resolver')
    );
  }

  /**
   * Initialise a MicrositeAdminController instance.
   *
   * @param \Drupal\domain_group\DomainGroupResolverInterface $domain_group_resolver
   *   The domain group resolver service.
   */
  public function __construct(DomainGroupResolverInterface $domain_group_resolver) {
    $this->domainGroupResolver = $domain_group_resolver;
  }

  /**
   * Redirect to microsite admin page /group/{group ID}.
   */
  public function redirectToMicrositeAdmin() {

    $group_id = $this->domainGroupResolver->getActiveDomainGroupId();
    $url = Url::fromRoute('entity.group.canonical', ['group' => $group_id]);
    return new RedirectResponse($url->toString());
  }

  /**
   * Access check to microsite admin page redirect.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {

    if (!is_null($this->domainGroupResolver->getActiveDomainGroupId())) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
