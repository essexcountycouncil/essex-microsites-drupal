<?php

namespace Drupal\group_webform\Plugin\Group\Relation;

use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Drupal\webform\Entity\Webform;
use Drupal\Component\Plugin\Derivative\DeriverBase;

class GroupWebformDeriver extends DeriverBase {

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    assert($base_plugin_definition instanceof GroupRelationTypeInterface);
    $this->derivatives = [];

    foreach (Webform::loadMultiple() as $name => $webform) {
      $label = $webform->label();

      $this->derivatives[$name] = clone $base_plugin_definition;
      $this->derivatives[$name]->set('entity_bundle', $name);
      $this->derivatives[$name]->set('label', t('Group webform (@label)', ['@label' => $label]));
      $this->derivatives[$name]->set('description', t('Adds %label webform to groups.', ['%label' => $label]));
    }

    return $this->derivatives;
  }

}
