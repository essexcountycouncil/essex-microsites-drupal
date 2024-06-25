<?php

namespace Drupal\group_permissions;

use Drupal\group\Entity\GroupInterface;

/**
 * Group permissions manager interface.
 */
interface GroupPermissionsManagerInterface {

  /**
   * Helper function to get custom group permissions.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   Permissions array.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getCustomPermissions(GroupInterface $group);

  /**
   * Get group permission object.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermission|null
   *   Group permission.
   */
  public function getGroupPermission(GroupInterface $group);

  /**
   * Get all group permissions objects.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermissionInterface[]
   *   Group permissions list.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAll();

  /**
   * Retrieves Group permission entity for a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity to load the group content entities for.
   *
   * @return \Drupal\group\Entity\GroupPermissiontInterface|null
   *   The GroupPermission entity of given group OR NULL if not existing.
   */
  public function loadByGroup(GroupInterface $group);

  /**
   * Retrieves group non admin permissions.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity to load the group content entities for.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]|null
   *   List of non admin group roles.
   */
  public function getNonAdminRoles(GroupInterface $group);

}
