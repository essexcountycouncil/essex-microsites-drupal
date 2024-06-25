<?php

namespace Drupal\group_context_domain\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\group_context_domain\GroupFromDomainContextTrait;

/**
 * Sets the group tied to the domain as a context.
 */
class GroupFromDomainContext implements ContextProviderInterface {

  use GroupFromDomainContextTrait;
  use StringTranslationTrait;

  public function __construct(
    protected DomainNegotiatorInterface $domainNegotiator,
    protected EntityRepositoryInterface $entityRepository,
    TranslationInterface $string_translation,
  ) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    // Create an optional context definition for group entities.
    $context_definition = EntityContextDefinition::fromEntityTypeId('group')
      ->setRequired(FALSE);

    // Cache this context per group on the domain.
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['url.site.group']);

    // Create a context from the definition and retrieved or created group.
    $context = new Context($context_definition, $this->getGroupFromDomain());
    $context->addCacheableDependency($cacheability);

    return ['group' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = EntityContext::fromEntityTypeId('group', $this->t('Group from domain'));
    $context->getContextDefinition()->setDescription($this->t('Returns the group from the domain record if there is one. Can be configured on the domain record form.'));
    return ['group' => $context];
  }

}
