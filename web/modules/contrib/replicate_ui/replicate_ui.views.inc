<?php
use Drupal\Core\Entity\ContentEntityTypeInterface;

/**
 * Implements hook_views_data_alter().
 */
function replicate_ui_views_data_alter(array &$views_data) {
  $config = \Drupal::configFactory()->get('replicate_ui.settings');
  foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
    if ($entity_type instanceof ContentEntityTypeInterface && in_array($entity_type_id, (array) $config->get('entity_types')) && $entity_type->hasLinkTemplate('canonical')) {
      if ($base_table = $entity_type->getBaseTable()) {
        $views_data[$base_table]['replicate_ui_link'] = [
          'title' => t('Replicate link'),
          'help' => t('Provide a simple link to replicate the entity'),
          'field' => [
            'id' => 'replicate_ui_link',
          ],
        ];
      }
    }
  }
}
