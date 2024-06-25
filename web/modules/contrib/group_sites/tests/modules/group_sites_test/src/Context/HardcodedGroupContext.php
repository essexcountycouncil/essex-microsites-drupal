<?php

namespace Drupal\group_sites_test\Context;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Entity\Group;

/**
 * Sets the group with ID 1 as a context.
 */
class HardcodedGroupContext implements ContextProviderInterface {

  use StringTranslationTrait;

  public function __construct(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $context_definition = EntityContextDefinition::fromEntityTypeId('group');
    $context = new Context($context_definition, Group::load(1));
    return ['group' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = EntityContext::fromEntityTypeId('group', $this->t('Hard-coded group'));
    $context->getContextDefinition()->setDescription($this->t('Returns the group with ID #1.'));
    return ['group' => $context];
  }

}
