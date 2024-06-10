<?php

namespace Drupal\group_permissions\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Group permission entity.
 *
 * @ingroup group_permissions
 *
 * @ContentEntityType(
 *   id = "group_permission",
 *   label = @Translation("Group permission"),
 *   handlers = {
 *     "storage" = "Drupal\group_permissions\Entity\Storage\GroupPermissionStorage",
 *     "storage_schema" = "Drupal\group_permissions\GroupPermissionStorageSchema",
 *     "access" = "Drupal\group_permissions\GroupPermissionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\group_permissions\GroupPermissionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "group_permission",
 *   revision_table = "group_permission_revision",
 *   admin_permission = "administer group permission entities",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "gid" = "gid",
 *     "owner" = "uid",
 *     "permissions" = "permissions",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/group/{group}/permissions",
 *     "delete-form" = "/group/{group}/permissions/delete",
 *     "version-history" = "/group/{group}/permissions/revisions",
 *     "revision" = "/group/{group}/permissions/revision/{group_permission_revision}",
 *     "revision-revert" = "/group/{group}/permissions/{group_permission}/revision/{group_permission_revision}/revert",
 *     "revision-delete" = "/group/{group}/permissions/{group_permission}/revision/{group_permission_revision}/delete",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message"
 *   },
 * )
 */
class GroupPermission extends EditorialContentEntityBase implements GroupPermissionInterface {

  use EntityOwnerTrait;

  /**
   * Whether entity validation is required before saving the entity.
   *
   * @var bool
   * @see https://www.drupal.org/project/drupal/issues/2847319
   */
  protected $validationRequired = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->gid->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroup(GroupInterface $group) {
    return $this->gid = $group->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    return !empty($this->permissions->first()) ? $this->permissions->first()->getValue() : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setPermissions(array $permissions) {
    $this->permissions = $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['gid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Group'))
      ->setDescription(t('The group entity.'))
      ->setSetting('target_type', 'group')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE)
      ->addConstraint('UniqueReferenceField');

    $fields['permissions'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Permissions'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setDescription(t('Group permissions.'))
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the group permissions were created.'))
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed on'))
      ->setDescription(t('The time that the group permissions were last edited.'))
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE);

    $fields += self::publishedBaseFieldDefinitions($entity_type);
    $fields['status']->setTranslatable(FALSE);

    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields['uid']
      ->setLabel(t('Creator'))
      ->setDescription(t('The username of the group permissions creator.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    if (!$this->isNewRevision() && isset($this->original) && (!isset($record->revision_log_message) || $record->revision_log_message === '')) {
      // If we are updating an existing group permission without adding
      // a new revision, we need to make sure $entity->revision_log_message is
      // reset whenever it is empty. Therefore, this code allows us to avoid
      // clobbering an existing log entry with an empty one.
      $record->revision_log_message = $this->original->revision_log_message->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    // Invalidate cache explicitly.
    Cache::invalidateTags(['group_permissions']);
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $group = $this->getGroup();
    if ($group) {
      $uri_route_parameters['group'] = $group->id();
    }
    return $uri_route_parameters;
  }

}
