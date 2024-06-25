<?php

namespace Drupal\localgov_microsites_group\ContextProvider;

use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\group_context_domain\Context\GroupFromDomainContext;

/**
 * Sets the current group as a context on domains.
 *
 * @deprecated in localgov_microsites_group:4.0.0-alpha1 and is removed from
 * localgov_microsites_group:5.0.0.
 * Use \Drupal\group_context_domain\Context\GroupFromDomainContext.
 * @see https://www.drupal.org/project/group_sites/issues/3402181
 */
class DomainGroupContext extends GroupFromDomainContext {

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = EntityContext::fromEntityTypeId('group', $this->t('Group from domain (deprecated)'));
    $context->getContextDefinition()->setDescription($this->t('Provided originally by Domain Group. Returns the group from the domain record if there is one.'));
    return ['group' => $context];
  }

}
