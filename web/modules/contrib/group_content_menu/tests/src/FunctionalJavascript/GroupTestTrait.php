<?php

namespace Drupal\Tests\group_content_menu\FunctionalJavascript;

use Drupal\Tests\RandomGeneratorTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Provides a functionality for Group tests.
 *
 * @see https://www.drupal.org/project/group/issues/3177542#comment-14774325
 */
trait GroupTestTrait {

  use UserCreationTrait;
  use RandomGeneratorTrait;

  /**
   * A test user with group creation rights.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupCreator;

  /**
   * {@inheritdoc}
   */
  protected function groupSetUp() {
    // Make sure we do not use user 1.
    $this->createUser();

    // Create a user that will server as the group creator.
    $this->groupCreator = $this->createUser($this->getGlobalPermissions());
  }

  /**
   * Gets the global (site) permissions for the group creator.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getGlobalPermissions() {
    return [];
  }

  /**
   * Creates a group.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\Group
   *   The created group entity.
   */
  protected function createGroup(array $values = []) {
    $group = $this->getEntityTypeManager()->getStorage('group')->create($values + [
      'label' => $this->randomMachineName(),
    ]);
    $group->enforceIsNew();
    $group->save();
    return $group;
  }

  /**
   * Creates a group type.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\GroupType
   *   The created group type entity.
   */
  protected function createGroupType(array $values = []) {
    $storage = $this->getEntityTypeManager()->getStorage('group_type');
    $group_type = $storage->create($values + [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ]);
    $storage->save($group_type);
    return $group_type;
  }

  /**
   * Creates a group role.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\GroupRole
   *   The created group role entity.
   */
  protected function createGroupRole(array $values = []) {
    $storage = $this->getEntityTypeManager()->getStorage('group_role');
    $group_role = $storage->create($values + [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ]);
    $storage->save($group_role);
    return $group_role;
  }

  /**
   * Get entityTypeManager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The Entity Type Manager.
   */
  protected function getEntityTypeManager() {
    return $this->container->get('entity_type.manager');
  }

}
