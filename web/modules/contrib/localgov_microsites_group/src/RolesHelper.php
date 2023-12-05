<?php

namespace Drupal\localgov_microsites_group;

/**
 * Helper class to for Roles.
 */
class RolesHelper {

  /**
   * Microsites controller role machine name.
   */
  const MICROSITES_CONTROLLER_ROLE = 'microsites_controller';

  /**
   * Microsites administrator role machine name.
   */
  const MICROSITES_EDITOR_ROLE = 'microsites_trusted_editor';

  /**
   * Group admin role machine name.
   */
  const GROUP_ADMIN_ROLE = 'admin';

  /**
   * Group anonymous role machine name.
   */
  const GROUP_ANONYMOUS_ROLE = 'anonymous';

  /**
   * Group member role machine name.
   */
  const GROUP_MEMBER_ROLE = 'member';

  /**
   * Group outsider role machine name.
   */
  const GROUP_OUTSIDER_ROLE = 'outsider';

  /**
   * Groups to add permissions to.
   */
  const GROUPS = [
    'microsite',
  ];

  /**
   * Assign permissions to roles if module has defaults.
   */
  public static function assignModuleRoles($module) {
    if ($roles = self::getModuleRoles($module)) {

      // Add global permissions.
      if (isset($roles['global'])) {
        foreach ($roles['global'] as $role => $permissions) {
          \user_role_grant_permissions($role, $permissions);
        }
      }

      // Add group permissions.
      if (isset($roles['group'])) {
        foreach (RolesHelper::GROUPS as $group) {
          foreach ($roles['group'] as $role => $permissions) {
            $group_role = \Drupal::entityTypeManager()
              ->getStorage('group_role')
              ->load($group . '-' . $role);
            $group_role->grantPermissions($permissions);
            $group_role->save();
          }
        }
      }
    }
  }

  /**
   * Retrieve default role permissions from module if implemented.
   *
   * A module can implement the HOOK_localgov_microsites_roles_default which
   * returns:
   * array [
   *   'global' => [
   *     RolesHelper::MICROSITES_ROLE => [ 'permissions' ]
   *   ],
   *   'group' => [
   *     RolesHelper::GROUP_ROLE => [ 'permissions' ]
   *   ],
   * ].
   *
   * @param string $module
   *   Module name.
   *
   * @return array|void
   *   Array if implemented.
   */
  public static function getModuleRoles($module) {
    if (function_exists($module . '_localgov_microsites_roles_default')) {
      return \call_user_func($module . '_localgov_microsites_roles_default');
    }
  }

}
