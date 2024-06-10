<?php

namespace Drupal\ginvite;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\group\Entity\GroupRelationshipInterface;

/**
 * Wrapper class for a GroupRelationship entity representing an invitation.
 *
 * Should be loaded through the 'group.invitation_loader' service.
 */
class GroupInvitation implements CacheableDependencyInterface {

  /**
   * The group relationship entity to wrap.
   *
   * @var \Drupal\group\Entity\GroupRelationshipInterface
   */
  protected $groupRelationship;

  /**
   * Constructs a new GroupInvitation.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $group_relationship
   *   The group relationship entity representing the invitation.
   *
   * @throws \Exception
   *   Exception thrown when trying to instantiate this class with a
   *   GroupRelationship entity that was not based on the GroupInvitation content
   *   enabler plugin.
   */
  public function __construct(GroupRelationshipInterface $group_relationship) {
    if ($group_relationship->getPluginId() == 'group_invitation') {
      $this->groupRelationship = $group_relationship;
    }
    else {
      throw new \Exception('Trying to create a GroupInvitation from an incompatible GroupRelationship entity.');
    }
  }

  /**
   * Returns the fieldable GroupRelationship entity for the invitation.
   *
   * @return \Drupal\group\Entity\GroupRelationshipInterface
   *   The group relationship entity.
   */
  public function getGroupRelationship() {
    return $this->groupRelationship;
  }

  /**
   * Returns the group for the invitation.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The group entity where invite belongs.
   */
  public function getGroup() {
    return $this->groupRelationship->getGroup();
  }

  /**
   * Returns the user for the invitation.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity referenced in invitation.
   */
  public function getUser() {
    return $this->groupRelationship->getEntity();
  }

  /**
   * Returns the group roles for the invitation.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   An array of group roles, keyed by their ID.
   */
  public function getRoles() {
    /** @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface $group_role_storage */
    $group_role_storage = \Drupal::entityTypeManager()->getStorage('group_role');
    return $group_role_storage->loadByUserAndGroup($this->getUser(), $this->getGroup());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->getGroupRelationship()->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->getGroupRelationship()->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->getGroupRelationship()->getCacheMaxAge();
  }

}
