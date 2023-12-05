<?php

namespace Drupal\groupmedia\Plugin\Group\RelationHandler;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderTrait;

/**
 * Provides operations for the group_media relation plugin.
 */
class GroupMediaOperationProvider implements OperationProviderInterface {

  use OperationProviderTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * String translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Media type storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlEntityStorageInterface
   */
  protected $mediaTypeStorage;

  /**
   * Constructs a new GroupMembershipRequestOperationProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\OperationProviderInterface $parent
   *   The default operation provider.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(
    OperationProviderInterface $parent,
    AccountProxyInterface $current_user,
    TranslationInterface $string_translation,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->parent = $parent;
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
    $this->mediaTypeStorage = $entity_type_manager->getStorage('media_type');
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $operations = $this->parent->getGroupOperations($group);
    $media_bundle_id = $this->groupRelationType->getEntityBundle();
    $media_type = $this->mediaTypeStorage->load($media_bundle_id);

    if ($group->hasPermission("create {$this->pluginId} entity", $this->currentUser)) {
      $operations["groupmedia-create-{$media_bundle_id}"] = [
        'title' => $this->t('Create @type', ['@type' => $media_type->label()]),
        'url' => new Url('entity.group_relationship.create_form', [
          'group' => $group->id(),
          'plugin_id' => $this->pluginId,
        ]),
        'weight' => 30,
      ];
    }

    return $operations;
  }

}
