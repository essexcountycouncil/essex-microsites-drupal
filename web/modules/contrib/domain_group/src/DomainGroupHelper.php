<?php

namespace Drupal\domain_group;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper methods for Domain Group.
 *
 * @deprecated
 * Use service domain_group_resolver.
 */
class DomainGroupHelper implements ContainerInjectionInterface {

  /**
   * Domain group resolver.
   *
   * @var \Drupal\domain_group\DomainGroupResolverInterface
   */
  protected $domainGroupResolver;

  /**
   * DomainGroupHelper constructor.
   *
   * @param \Drupal\domain_group\DomainGroupResolverInterface $domain_group_resolver
   *   The domain group resolver.
   */
  public function __construct(DomainGroupResolverInterface $domain_group_resolver) {
    $this->domainGroupResolver = $domain_group_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('domain_group_resolver')
    );
  }

  /**
   * Get Group ID for active domain if there is one.
   *
   * @return int|null
   *   Group ID, or NULL if there is no active domain group.
   */
  public function getActiveDomainGroup(): ?int {
    return $this->domainGroupResolver->getActiveDomainGroupId();
  }

}
