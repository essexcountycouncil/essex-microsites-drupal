<?php

namespace Drupal\ecc_waste\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Applies a user-selected waste item taxonomy term to a set of nodes.
 *
 * @Action(
 *   id = "apply_waste_item_term_action",
 *   label = @Translation("Apply Waste Item Term"),
 *   type = "node"
 * )
 */
class ApplyWasteItem extends ViewsBulkOperationsActionBase implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // @see Drupal\Core\Field\FieldUpdateActionBase::access().
    return $object->access('update', $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $node = $entity;
    // Apply the taxonomy term to the node.
    if ($node->hasField('field_disposal_option_items')) {
      $tid = $this->configuration['taxonomy_term_tid'];
      $field_name = 'field_disposal_option_items';

      // Get the field values.
      $current_terms = $node->get($field_name)->getValue();

      // Check if the term is already attached.
      $term_exists = false;
      foreach ($current_terms as $term) {
        if ($term['target_id'] == $tid) {
          $term_exists = true;
          break;
        }
      }

      // If term doesn't exist, add it.
      if (!$term_exists) {
        $current_terms[] = ['target_id' => $tid];
        $node->set($field_name, $current_terms);
        $node->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Load vocabulary terms.
    $vocabulary = 'waste_item';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary);

    // Build options array for the select element.
    $options = [];
    foreach ($terms as $term) {
      $options[$term->tid] = str_repeat('-', $term->depth) . $term->name;
    }

    $form['taxonomy_term_tid'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Waste Item'),
      '#options' => $options,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['taxonomy_term_tid'] = $form_state->getValue('taxonomy_term_tid');
  }

}
