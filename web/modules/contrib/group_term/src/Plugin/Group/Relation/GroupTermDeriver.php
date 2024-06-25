<?php

namespace Drupal\group_term\Plugin\Group\Relation;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Provides derivative definitions for vocabularies.
 */
class GroupTermDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    assert($base_plugin_definition instanceof GroupRelationTypeInterface);
    $this->derivatives = [];

    foreach (Vocabulary::loadMultiple() as $name => $vocabulary) {
      $label = $vocabulary->label();

      $this->derivatives[$name] = clone $base_plugin_definition;
      $this->derivatives[$name]->set('entity_bundle', $name);
      $this->derivatives[$name]->set('label', $this->t('Group term (@label)', ['@label' => $label]));
      $this->derivatives[$name]->set('description', $this->t('Adds %type terms to groups.', ['%type' => $label]));
    }

    return $this->derivatives;
  }

}
