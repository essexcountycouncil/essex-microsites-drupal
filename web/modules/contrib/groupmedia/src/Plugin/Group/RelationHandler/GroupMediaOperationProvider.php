<?php

namespace Drupal\groupmedia\Plugin\Group\RelationHandler;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderTrait;
use Drupal\media\Entity\MediaType;

/**
 * Provides operations for the group_media relation plugin.
 */
class GroupMediaOperationProvider implements OperationProviderInterface {

  use OperationProviderTrait;

  /**
   * Constructs a new GroupMembershipRequestOperationProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\OperationProviderInterface $parent
   *   The default operation provider.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(OperationProviderInterface $parent, AccountProxyInterface $current_user, TranslationInterface $string_translation) {
    $this->parent = $parent;
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $media_bundle_id = $this->groupRelationType->getEntityBundle();
    $media_type = MediaType::load($media_bundle_id);
    $operations = [];

    if ($group->hasPermission("create {$this->pluginId} entity", $this->currentUser)) {
      $operations["groupmedia-create-{$media_bundle_id}"] = [
        'title' => $this->t('Create @type', ['@type' => $media_type->label()]),
        'url' => new Url('entity.group_relationship.create_form', [
          'group' => $group->id(),
          'plugin_id' => $this->pluginId
        ]),
        'weight' => 30,
      ];
    }

    return $operations;
  }

}
