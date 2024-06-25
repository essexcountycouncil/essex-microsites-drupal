<?php

/**
 * @file
 * Builds placeholder replacement tokens for group term-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function group_term_token_info() {
  $tokens = [];

  $tokens['group'] = [
    'name' => t('Group'),
    'description' => t('The parent group.'),
    'type' => 'group',
  ];

  if (\Drupal::moduleHandler()->moduleExists('token')) {
    $tokens['groups'] = [
      'name' => t('Groups'),
      'description' => t('An array of all the term parent groups.'),
      'type' => 'array',
    ];
  }

  return [
    'tokens' => ['term' => $tokens],
  ];
}

/**
 * Implements hook_tokens().
 */
function group_term_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  if ($type != 'term' || empty($data['term']) || !$data['term']->id()) {
    return [];
  }

  $group_relationship_storage = \Drupal::entityTypeManager()->getStorage('group_relationship');
  $group_relationships = $group_relationship_storage->loadByEntity($data['term']);
  if (empty($group_relationships)) {
    return [];
  }

  $token = \Drupal::token();
  $replacements = [];

  $groups = [];
  /** @var \Drupal\group\Entity\GroupRelationshipInterface $group_relationship */
  foreach ($group_relationships as $group_relationship) {
    $group = $group_relationship->getGroup();
    $groups[$group->id()] = $group->label();
    $bubbleable_metadata->addCacheableDependency($group);
  }

  if (isset($tokens['groups']) && function_exists('token_render_array')) {
    $replacements[$tokens['groups']] = token_render_array($groups, $options);
  }

  // [term:groups:*] chained tokens.
  if ($parents_tokens = $token->findWithPrefix($tokens, 'groups')) {
    $replacements += $token->generate('array', $parents_tokens, ['array' => $groups], $options, $bubbleable_metadata);
  }

  /** @var \Drupal\group\Entity\GroupRelationshipInterface $group_relationship */
  $group_relationship = array_pop($group_relationships);
  $group = $group_relationship->getGroup();
  if (isset($tokens['group'])) {
    $replacements[$tokens['group']] = $group->label();
  }

  $langcode = $data['term']->language()->getId();
  if ($group->hasTranslation($langcode)) {
    $group = $group->getTranslation($langcode);
  }

  if ($group_tokens = $token->findWithPrefix($tokens, 'group')) {
    $replacements += $token->generate('group', $group_tokens, ['group' => $group], $options, $bubbleable_metadata);
  }

  return $replacements;
}
