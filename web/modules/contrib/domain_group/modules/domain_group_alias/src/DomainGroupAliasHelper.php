<?php

namespace Drupal\domain_group_alias;

use Drupal\Core\Form\FormStateInterface;
use Drupal\domain_path\DomainPathHelper;

/**
 * Extend the DomainPathHelper class.
 */
class DomainGroupAliasHelper extends DomainPathHelper {

  /**
   * {@inheritdoc}
   */
  public static function validateEntityForm(array &$form, FormStateInterface $form_state) {
    // Set up variables.
    $entity = $form_state->getFormObject()->getEntity();
    $domain_path_storage = \Drupal::service('entity_type.manager')->getStorage('domain_path');
    $path_values = $form_state->getValue('path');
    $domain_path_values = $form_state->getValue('domain_path');

    if (!empty($path_values[0]['pathauto']) || !empty($domain_path_values['domain_group_alias_auto'])) {
      // Skip validation if checked automatically generate alias.
      return;
    }

    // If we're just deleting the domain paths we don't have to validate
    // anything.
    if (!empty($domain_path_values['domain_path_delete'])) {
      return;
    }
    unset($domain_path_values['domain_path_delete']);
    $alias = isset($path_values[0]['alias']) ? $path_values[0]['alias'] : NULL;

    // Check domain access settings if they are on the form.
    $domain_access = [];
    if (!empty($form['field_domain_access'])) {
      foreach ($form_state->getValue('field_domain_access') as $item) {
        $domain_access[$item['target_id']] = $item['target_id'];
      }
    }
    // If domain access is on for this form, we check the "all affiliates"
    // checkbox, otherwise we just assume it's available on all domains.
    $domain_access_all = !empty($form['field_domain_all_affiliates'])
      ? $form_state->getValue('field_domain_all_affiliates')['value'] : TRUE;

    // Validate each path value.
    foreach ($domain_path_values as $domain_id => $path) {

      // Don't validate if the domain doesn't have access (we remove aliases
      // for domains that don't have access to this entity).
      $domain_has_access = $domain_access_all || ($domain_access && !empty($domain_access[$domain_id]));
      if (!$domain_has_access) {
        continue;
      }
      if (!empty($path) && $path == $alias) {
        $form_state->setError($form['path']['domain_path'][$domain_id], t('Domain path "%path" matches the default path alias. You may leave the element blank.', ['%path' => $path]));
      }
      elseif (!empty($path)) {
        // Trim slashes and whitespace from end of path value.
        $path_value = rtrim(trim($path), " \\/");

        // Check that the paths start with a slash.
        if ($path_value && $path_value[0] !== '/') {
          $form_state->setError($form['path']['domain_path'][$domain_id], t('Domain path "%path" needs to start with a slash.', ['%path' => $path]));
        }

        // Check for duplicates.
        $entity_query = $domain_path_storage->getQuery();
        $entity_query->condition('domain_id', $domain_id)
          ->condition('alias', $path);
        if (!$entity->isNew()) {
          $entity_query->condition('source', '/' . $entity->toUrl()->getInternalPath(), '<>');
        }
        $result = $entity_query->execute();
        if ($result) {
          $form_state->setError($form['path']['domain_path'][$domain_id], t('Domain path %path matches an existing domain path alias', ['%path' => $path]));
        }
      }
    }
  }

}
