<?php

declare(strict_types=1);

namespace Drupal\group_context_domain;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Trait to get the group entity from the current domain.
 *
 * Using this trait will add the getGroupFromDomain() method to the class.
 *
 * If the class is capable of injecting services from the container, it should
 * inject the 'domain.negotiator' and 'entity.repository' services and assign
 * them to the domainNegotiator and entityRepository properties.
 */
trait GroupFromDomainContextTrait {

  /**
   * The domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected DomainNegotiatorInterface $domainNegotiator;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * Gets the domain negotiator.
   *
   * @return \Drupal\domain\DomainNegotiatorInterface
   *   The domain negotiator.
   */
  protected function getDomainNegotiator(): DomainNegotiatorInterface {
    if (!$this->domainNegotiator) {
      $this->domainNegotiator = \Drupal::service('domain.negotiator');
    }
    return $this->domainNegotiator;
  }

  /**
   * Gets the entity repository.
   *
   * @return \Drupal\Core\Entity\EntityRepositoryInterface
   *   The entity repository.
   */
  protected function getEntityRepository(): EntityRepositoryInterface {
    if (!$this->entityRepository) {
      $this->entityRepository = \Drupal::service('entity.repository');
    }
    return $this->entityRepository;
  }

  /**
   * Retrieves the group entity from the current domain.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   A group entity if one could be found, NULL otherwise.
   */
  public function getGroupFromDomain(): GroupInterface|null {
    if ($domain = $this->getDomainNegotiator()->getActiveDomain()) {
      if ($uuid = $domain->getThirdPartySetting('group_context_domain', 'group_uuid')) {
        return $this->getEntityRepository()->loadEntityByUuid('group', $uuid);
      }
    }
    return NULL;
  }

}
