<?php

declare(strict_types=1);

namespace Drupal\localgov_microsites_group;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;

/**
 * Content types provided by modules and if they are used by a microsite.
 */
class ContentTypeHelper implements ContentTypeHelperInterface {

  /**
   * Constructs a GroupPermissionsHelper instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $relationPluginManager
   *   The module handler.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager, protected ModuleHandlerInterface $moduleHandler, protected GroupRelationTypeManagerInterface $relationPluginManager) {
  }

  /**
   * {@inheritdoc}
   */
  public function modulesList(GroupInterface $group): array {
    static $modules = [];

    $this->moduleHandler->invokeAllWith('localgov_microsites_roles_default', function ($hook, $module) use (&$modules) {
      $modules[$module] = self::ENABLED;
    });
    if ($group->hasField('lgms_modules_disabled')) {
      foreach ($group->get('lgms_modules_disabled') as $row) {
        $modules[$row->value] = self::DISABLED;
      }
    }
    unset($modules['localgov_microsites_group']);

    return $modules;
  }

  /**
   * {@inheritdoc}
   */
  public function moduleStatus($check_module, GroupInterface $group): string {
    return $this->modulesList($group)[$check_module] ?? self::NOT_APPLICABLE;
  }

  /**
   * {@inheritdoc}
   */
  public function moduleEnable($enable_module, GroupInterface $group) {
    if ($this->modulesList($group)[$enable_module] == self::DISABLED) {
      $modules = $group->get('lgms_modules_disabled')->getValue();
      unset($modules[array_search(['value' => $enable_module], $modules)]);
      $group->lgms_modules_disabled = array_values($modules);
      $group->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function moduleDisable($disable_module, GroupInterface $group) {
    if ($this->modulesList($group)[$disable_module] == self::ENABLED) {
      $group->get('lgms_modules_disabled')->appendItem($disable_module);
      $group->save();
    }
  }

  /**
   * All 'group_entity_type:entity_bundles' enabled for a microsite.
   *
   * This is called a lot. It could maybe be better cached. It could also maybe
   * better not reuse the roles hook?
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to check.
   *
   * @return string[]
   *   All 'group_entity_type:entity_bundles' enabled.
   */
  public function enabledContentTypes(GroupInterface $group): array {
    static $plugin_types = [];
    static $all_content_types = [];
    static $group_content_types = [];

    // All possible content types.
    if (empty($plugin_types[$group->getGroupType()->id()])) {
      $plugin_types[$group->getGroupType()->id()] = $this->relationPluginManager->getInstalledIds($group->getGroupType());
    }
    // All types controlled by modules that can be disabled.
    if (empty($all_content_types)) {
      $this->moduleHandler->invokeAllWith('localgov_microsites_roles_default', function ($hook, $module) use (&$all_content_types) {
        $permissions = $hook()['group'][RolesHelper::GROUP_ADMIN_ROLE] ?? [];
        foreach ($permissions as $permission) {
          $matches = [];
          if (preg_match('/^create (.*) entity$/', $permission, $matches)) {
            $all_content_types[$module][] = $matches[1];
          }
        }
      });
      unset($all_content_types['localgov_microsites_group']);
    }
    // Remove disabled modules content types.
    if (empty($group_content_types[$group->id()])) {
      $group_content_types[$group->id()] = $all_content_types;
      if ($group->hasField('lgms_modules_disabled')) {
        foreach ($group->get('lgms_modules_disabled') as $row) {
          unset($group_content_types[$group->id()][$row->value]);
        }
      }
      // Add in any content types not controlled.
      $group_content_types[$group->id()]['other'] = array_diff($plugin_types[$group->getGroupType()->id()], ...array_values($all_content_types));
    }

    return array_merge(...array_values($group_content_types[$group->id()]));
  }

}
