<?php

namespace Drupal\group_term\Plugin\Group\Relation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a group relation type plugin for taxonomy terms.
 *
 * @GroupRelationType(
 *   id = "group_term",
 *   label = @Translation("Group term"),
 *   description = @Translation("Adds taxonomy terms to a group."),
 *   entity_type_id = "taxonomy_term",
 *   entity_access = TRUE,
 *   deriver = "Drupal\group_term\Plugin\Group\Relation\GroupTermDeriver",
 * )
 */
class GroupTerm extends GroupRelationBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other group relations.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $dependencies['config'][] = 'taxonomy.vocabulary.' . $this->getRelationType()->getEntityBundle();
    return $dependencies;
  }

}
