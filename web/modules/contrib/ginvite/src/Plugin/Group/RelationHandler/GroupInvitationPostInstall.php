<?php

namespace Drupal\ginvite\Plugin\Group\RelationHandler;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\ginvite\Plugin\Group\Relation\GroupInvitation;
use Drupal\group\Entity\GroupRelationshipTypeInterface;
use Drupal\group\Plugin\Group\RelationHandler\PostInstallInterface;
use Drupal\group\Plugin\Group\RelationHandler\PostInstallTrait;

/**
 * Provides post install tasks for the group_invitation relation plugin.
 */
class GroupInvitationPostInstall implements PostInstallInterface {

  use PostInstallTrait;
  use StringTranslationTrait;

  /**
   * Constructs a new GroupInvitationPostInstall.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PostInstallInterface $parent
   *   The default post install handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(PostInstallInterface $parent, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->parent = $parent;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstallTasks() {
    $tasks = $this->parent->getInstallTasks();
    $tasks['install-group-invitation-fields'] = [$this, 'installGroupInvitationFields'];
    return $tasks;
  }

  /**
   * Installs group membership request fields.
   *
   * @param \Drupal\group\Entity\GroupRelationshipTypeInterface $relationship_type
   *   The GroupRelationshipType created by installing the plugin.
   * @param $is_syncing
   *   Whether config is syncing.
   */
  public function installGroupInvitationFields(GroupRelationshipTypeInterface $relationship_type, $is_syncing) {
    // Only create config objects while config import is not in progress.
    if ($is_syncing === TRUE) {
      return;
    }

    $relationship_type_id = $relationship_type->id();

    // Add the group_roles field to the newly added group relationship type.
    // The field storage for this is defined in the config/install folder. The
    // default handler for 'group_role' target entities in the 'group_type'
    // handler group is GroupTypeRoleSelection.
    FieldConfig::create([
      'field_storage' => FieldStorageConfig::loadByName('group_relationship', 'group_roles'),
      'bundle' => $relationship_type_id,
      'label' => $this->t('Roles'),
      'settings' => [
        'handler' => 'group_type:group_role',
        'handler_settings' => [
          'group_type_id' => $relationship_type->getGroupTypeId(),
        ],
      ],
    ])->save();

    // Add email field.
    FieldConfig::create([
      'field_storage' => FieldStorageConfig::loadByName('group_relationship', 'invitee_mail'),
      'bundle' => $relationship_type_id,
      'label' => $this->t('Invitee mail'),
      'required' => TRUE,
    ])->save();

    // Add status field.
    FieldConfig::create([
      'field_storage' => FieldStorageConfig::loadByName('group_relationship', 'invitation_status'),
      'bundle' => $relationship_type_id,
      'label' => $this->t('Invitation status'),
      'required' => TRUE,
      'default_value' => [
        [
          'value' => GroupInvitation::INVITATION_PENDING,
        ],
      ],
    ])->save();

    // Build the 'default' display ID for both the entity form and view mode.
    $default_display_id = "group_relationship.$relationship_type_id.default";

    $entity_form_display_storage = $this->entityTypeManager->getStorage('entity_form_display');

    // Build or retrieve the 'default' form mode.
    if (!$form_display = $entity_form_display_storage->load($default_display_id)) {
      $form_display = $entity_form_display_storage->create([
        'targetEntityType' => 'group_relationship',
        'bundle' => $relationship_type_id,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    $entity_view_display_storage = $this->entityTypeManager->getStorage('entity_view_display');
    // Build or retrieve the 'default' view mode.
    if (!$view_display = $entity_view_display_storage->load($default_display_id)) {
      $view_display = $entity_view_display_storage->create([
        'targetEntityType' => 'group_relationship',
        'bundle' => $relationship_type_id,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    // Assign widget settings for the 'default' form mode.
    $form_display
      ->setComponent('group_roles', [
        'type' => 'options_buttons',
      ])
      ->setComponent('invitee_mail', [
        'type' => 'email_default',
        'weight' => -1,
        'settings' => [
          'placeholder' => 'example@example.com',
        ],
      ])
      ->removeComponent('entity_id')
      ->removeComponent('path')
      ->save();

    // Assign display settings for the 'default' view mode.
    $view_display
      ->setComponent('group_roles', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'settings' => [
          'link' => 0,
        ],
      ])
      ->setComponent('invitee_mail', [
        'type' => 'email_mailto',
      ])
      ->setComponent('invitation_status', [
        'type' => 'number_integer',
      ])
      ->save();
  }

}
