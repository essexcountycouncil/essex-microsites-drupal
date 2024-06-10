<?php

namespace Drupal\ginvite\Plugin\Group\RelationHandler;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderTrait;

/**
 * Provides operations for the group_invitation relation plugin.
 */
class GroupInvitationOperationProvider implements OperationProviderInterface {

  use OperationProviderTrait;

  /**
   * Constructs a new GroupInvitationOperationProvider.
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
    $operations = $this->parent->getGroupOperations($group);

    if ($group->hasPermission('invite users to group', $this->currentUser)) {
      $operations['invite-user'] = [
        'title' => $this->t('Invite user'),
        'url' => new Url('entity.group_relationship.add_form', [
          'group' => $group->id(),
          'plugin_id' => 'group_invitation',
        ]),
        'weight' => 0,
      ];
    }

    return $operations;
  }

}
