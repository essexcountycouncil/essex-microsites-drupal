<?php

namespace Drupal\domain_group\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\domain_group\DomainGroupResolverInterface;
use Drupal\group\Entity\Group;

/**
 * Sets the current group as a context on domains.
 */
class DomainGroupContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * Domain group resolver.
   *
   * @var \Drupal\domain_group\DomainGroupResolverInterface
   */
  protected $domainGroupResolver;

  /**
   * Constructs a new NodeRouteContext.
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
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context_definition = EntityContextDefinition::create('group')->setRequired(FALSE);
    $group = NULL;
    if ($gid = $this->domainGroupResolver->getActiveDomainGroupId()) {
      $group = Group::load($gid);
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['url.site']);

    $context = new Context($context_definition, $group);
    $context->addCacheableDependency($cacheability);
    $result['group'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = EntityContext::fromEntityTypeId('group', $this->t('Group from domain'));
    return ['group' => $context];
  }

}
