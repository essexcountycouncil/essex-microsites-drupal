<?php

namespace Drupal\groupmedia\Plugin\Group\Relation;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Drupal\media\Entity\MediaType;

/**
 * Class Group Media Deriver.
 *
 * @package Drupal\groupmedia\Plugin\Group\Relation\GroupMedia
 */
class GroupMediaDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    assert($base_plugin_definition instanceof GroupRelationTypeInterface);
    $this->derivatives = [];

    foreach (MediaType::loadMultiple() as $name => $media_type) {
      $label = $media_type->label();

      $this->derivatives[$name] = clone $base_plugin_definition;
      $this->derivatives[$name]->set('entity_bundle', $name);
      $this->derivatives[$name]->set('label', $this->t('Group media (@type)', ['@type' => $label]));
      $this->derivatives[$name]->set('description', $this->t('Adds %type content to groups both publicly and privately.', ['%type' => $label]));
    }

    return $this->derivatives;
  }

}
