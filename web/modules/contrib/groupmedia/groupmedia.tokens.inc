<?php

/**
 * @file
 * Builds placeholder replacement tokens for group media-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationship;

/**
 * Implements hook_token_info_alter().
 */
function groupmedia_token_info_alter(&$info) {
  $info['tokens']['group']['url']['type'] = 'url';
}

/**
 * Implements hook_token_info().
 */
function groupmedia_token_info() {
  $tokens = [];

  $tokens['group'] = [
    'name' => t('Group'),
    'description' => t('The parent group.'),
    'type' => 'group',
  ];

  if (\Drupal::moduleHandler()->moduleExists('token')) {
    $tokens['groups'] = [
      'name' => t('Groups'),
      'description' => t('An array of all the media parent groups.'),
      'type' => 'array',
    ];
  }

  return [
    'tokens' => [
      'media' => $tokens,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function groupmedia_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  if ($type != 'media' || empty($data['media'])) {
    return [];
  }

  $token_service = \Drupal::token();
  $replacements = [];

  if (!$data['media']->id()) {
    return [];
  }

  $groups = [];
  $group_relationship_array = GroupRelationship::loadByEntity($data['media']);
  if (empty($group_relationship_array)) {
    // Attempt to find group from group route context.
    if ($context = \Drupal::service('group.group_route_context')->getRuntimeContexts(['group'])['group']) {
      $group = $context->getContextValue();
      if (!($group instanceof GroupInterface)) {
        return [];
      }
      $groups[$group->id()] = $group->label();
      $bubbleable_metadata->addCacheableDependency($group);
    }
    else {
      return [];
    }
  }

  /** @var \Drupal\group\Entity\GroupRelationshipInterface $group_relationship */
  foreach ($group_relationship_array as $group_relationship) {
    $group = $group_relationship->getGroup();
    $groups[$group->id()] = $group->label();
    $bubbleable_metadata->addCacheableDependency($group);
  }

  if (isset($tokens['groups'])) {
    $replacements[$tokens['groups']] = token_render_array($groups, $options);
  }

  // [media:groups:*] chained tokens.
  if ($parents_tokens = \Drupal::token()->findWithPrefix($tokens, 'groups')) {
    $replacements += \Drupal::token()->generate('array', $parents_tokens, ['array' => $groups], $options, $bubbleable_metadata);
  }

  if (!empty($group_relationship_array)) {
    /** @var \Drupal\group\Entity\GroupRelationshipInterface $group_relationship */
    $group_relationship = array_pop($group_relationship_array);
    $group = $group_relationship->getGroup();
  }
  if (isset($tokens['group'])) {
    $replacements[$tokens['group']] = $group->label();
  }

  $langcode = $data['media']->language()->getId();
  if ($group->hasTranslation($langcode)) {
    $group = $group->getTranslation($langcode);
  }

  if ($group_tokens = $token_service->findWithPrefix($tokens, 'group')) {
    $replacements += $token_service->generate('group', $group_tokens, ['group' => $group], $options, $bubbleable_metadata);
  }

  return $replacements;
}
