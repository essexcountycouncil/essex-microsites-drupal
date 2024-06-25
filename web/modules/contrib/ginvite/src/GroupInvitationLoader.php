<?php

namespace Drupal\ginvite;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\ginvite\Plugin\Group\Relation\GroupInvitation;
use Drupal\ginvite\GroupInvitation as GroupInvitationWrapper;

/**
 * Loader for wrapped GroupRelationship entities using the 'group_invitation' plugin.
 */
class GroupInvitationLoader implements GroupInvitationLoaderInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user's account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The group relationship type storage.
   *
   * @var \Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface
   */
  protected $groupRelationshipTypeStorage;

  /**
   * The group relationship storage.
   *
   * @var \Drupal\group\Entity\Storage\GroupRelationshipStorageInterface
   */
  protected $groupRelationshipStorage;

  /**
   * Constructs a new GroupInvitationLoader.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->groupRelationshipTypeStorage = $this->entityTypeManager->getStorage('group_relationship_type');
    $this->groupRelationshipStorage = $this->entityTypeManager->getStorage('group_relationship');
  }

  /**
   * Wraps GroupRelationship entities in a GroupInvitation object.
   *
   * @param array $filters
   *   An associative array where the keys are the property names and the
   *   values are the values those properties must have.
   *
   * @return \Drupal\ginvite\GroupInvitation[]
   *   A list of GroupInvitation wrapper objects.
   */
  protected function loadGroupInvitations(array $filters) {
    $group_invitations = [];

    $group_relationships = $this->groupRelationshipStorage->loadByProperties($filters);
    foreach ($group_relationships as $group_relationship) {
      $group_invitations[] = new GroupInvitationWrapper($group_relationship);
    }
    return $group_invitations;
  }

  /**
   * {@inheritdoc}
   */
  public function load(GroupInterface $group, AccountInterface $account) {
    $filters = [
      'gid' => $group->id(),
      'entity_id' => $account->id(),
      'plugin_id' => 'group_invitation',
    ];

    $group_invitations = $this->loadByProperties($filters);
    return $group_invitations ? reset($group_invitations) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByGroup(GroupInterface $group, $roles = NULL, $mail = NULL, $status = GroupInvitation::INVITATION_PENDING) {
    $filters = [
      'gid' => $group->id(),
      'invitation_status' => $status,
      'plugin_id' => 'group_invitation',
    ];

    if (isset($roles)) {
      $filters['group_roles'] = (array) $roles;
    }
    if (isset($mail)) {
      $filters['invitee_mail'] = $mail;
    }

    return $this->loadByProperties($filters);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByUser(AccountInterface $account = NULL, $roles = NULL, $status = GroupInvitation::INVITATION_PENDING) {
    if (!isset($account)) {
      $account = $this->currentUser;
    }

    if ($account->isAnonymous() || !$account->getEmail()) {
      return [];
    }

    $filters = [
      'entity_id' => $account->id(),
      'invitation_status' => $status,
      'invitee_mail' => $account->getEmail(),
    ];

    if (isset($roles)) {
      $filters['group_roles'] = (array) $roles;
    }

    return $this->loadByProperties($filters);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $filters = []) {
    $group_relationship_type_ids = $this->loadGroupRelationshipTypeIds();

    // If none were found, there can be no invitations either.
    if (empty($group_relationship_type_ids)) {
      return [];
    }

    $values['type'] = $group_relationship_type_ids;

    return $this->loadGroupInvitations($filters);
  }

  /**
   * Load group relationship type ids.
   *
   * @return array
   *   Group relationship type ids.
   */
  protected function loadGroupRelationshipTypeIds() {
    $group_relationship_type_ids = [];
    // Load all group content types for the invitation group relation plugin.
    $group_relationship_types = $this->groupRelationshipTypeStorage
      ->loadByPluginId('group_invitation');

    // If none were found, there can be no invitations either.
    if (empty($group_relationship_types)) {
      return $group_relationship_type_ids;
    }

    foreach ($group_relationship_types as $group_relationship_type) {
      $group_relationship_type_ids[] = $group_relationship_type->id();
    }

    return $group_relationship_type_ids;
  }

}
