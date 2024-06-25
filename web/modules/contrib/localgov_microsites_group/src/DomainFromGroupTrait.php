<?php

namespace Drupal\localgov_microsites_group;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\domain\DomainInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Trait to get the domain from group.
 *
 * If the class is capable of injecting services from the container it should
 * inject the 'entity_type.manager' into the entityTypeManager property.
 */
trait DomainFromGroupTrait {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Gets the EntityTypeManagerInterface.
   */
  protected function getEntityTypeManager(): EntityTypeManagerInterface {
    if (!$this->entityTypeManager) {
      $this->entityTypeManager = \Drupal::service('entity_type.manager');
    }
    return $this->entityTypeManager;
  }

  /**
   * Retrieves the domain config entity for a group.
   */
  public function getDomainFromGroup(GroupInterface $group): ?DomainInterface {
    $result = $this->getEntityTypeManager()->getStorage('domain')->loadByProperties(['third_party_settings.group_context_domain.group_uuid' => $group->uuid()]);
    return reset($result) ?: NULL;
  }

}
