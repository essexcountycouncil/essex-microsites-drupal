<?php

namespace Drupal\group_term\Plugin\Group\RelationHandler;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderTrait;

/**
 * Provides operations for the group_term relation plugin.
 */
class GroupTermOperationProvider implements OperationProviderInterface {

  use OperationProviderTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   *   The entity type manager.
   */
  public function __construct(OperationProviderInterface $parent, AccountProxyInterface $current_user, TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager) {
    $this->parent = $parent;
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $operations = $this->parent->getGroupOperations($group);
    $vocabulary_id = $this->groupRelationType->getEntityBundle();
    $vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($vocabulary_id);

    if ($group->hasPermission("create {$this->pluginId} entity", $this->currentUser) || $this->currentUser->hasPermission('create any group_term entity')) {
      $route_params = [
        'group' => $group->id(),
        'plugin_id' => $this->pluginId,
      ];
      $operations["group_term-create-$vocabulary_id"] = [
        'title' => $this->t('Create @type', ['@type' => $vocabulary->label()]),
        'url' => new Url('entity.group_relationship.create_form', $route_params),
        'weight' => 30,
      ];
    }

    return $operations;
  }

}
