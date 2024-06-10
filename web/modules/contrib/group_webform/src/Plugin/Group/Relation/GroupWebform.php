<?php

namespace Drupal\group_webform\Plugin\Group\Relation;

use Drupal\group\Plugin\Group\Relation\GroupRelationBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a group relation type for nodes.
 *
 * @GroupRelationType(
 *   id = "group_webform",
 *   label = @Translation("Group webform"),
 *   description = @Translation("Adds webforms to groups both publicly and privately."),
 *   entity_type_id = "webform_submission",
 *   entity_access = TRUE,
 *   reference_label = @Translation("Name"),
 *   reference_description = @Translation("The name of the webform to add to the group"),
 *   deriver = "Drupal\group_webform\Plugin\Group\Relation\GroupWebformDeriver",
 * )
 */
class GroupWebform extends GroupRelationBase {

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
    $dependencies['config'][] = 'webform.webform.' . $this->getRelationType()->getEntityBundle();
    return $dependencies;
  }

}
