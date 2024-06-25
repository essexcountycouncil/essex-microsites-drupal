<?php

declare(strict_types=1);

namespace Drupal\Tests\group_context_domain\Traits;

use Drupal\domain\DomainInterface;

/**
 * Functionality trait for a group_membership bundle class.
 */
trait GroupContextDomainTestTrait {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a domain.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\domain\DomainInterface
   *   The created domain entity.
   */
  protected function createDomain(array $values = []): DomainInterface {
    $storage = $this->entityTypeManager->getStorage('domain');
    $domain = $storage->create($values + [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ]);
    $storage->save($domain);
    return $domain;
  }

}
