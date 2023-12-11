<?php

namespace Drupal\groupmedia\Plugin\Group\Relation;

use Drupal\group\Plugin\Group\Relation\GroupRelationBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a relation enabler for media.
 *
 * @GroupRelationType(
 *   id = "group_media",
 *   label = @Translation("Group media"),
 *   description = @Translation("Adds media items to groups both publicly and privately."),
 *   entity_type_id = "media",
 *   entity_access = TRUE,
 *   reference_label = @Translation("Title"),
 *   reference_description = @Translation("The title of the media to add to the group"),
 *   deriver = "Drupal\groupmedia\Plugin\Group\Relation\GroupMediaDeriver",
 * )
 */
class GroupMedia extends GroupRelationBase {

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
    // that's consistent with other group relation type plugins.
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
    $dependencies['config'][] = 'media.type.' . $this->getRelationType()->getEntityBundle();
    return $dependencies;
  }

}
