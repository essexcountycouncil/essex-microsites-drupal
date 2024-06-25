<?php

namespace Drupal\group_content_menu\Plugin\Group\Relation;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group_content_menu\Entity\GroupContentMenuType;

/**
 * Group menu deriver.
 */
class GroupMenuDeriver extends DeriverBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (GroupContentMenuType::loadMultiple() as $name => $group_menu_type) {
      $label = $group_menu_type->label();

      $this->derivatives[$name] = clone $base_plugin_definition;
      $this->derivatives[$name]->set('entity_bundle', $name);
      $this->derivatives[$name]->set('label', $this->t('Group menu (@type)', ['@type' => $label]));
      $this->derivatives[$name]->set('description', $this->t('Adds %type menu items to groups.', ['%type' => $label]));
    }

    return $this->derivatives;
  }

}
