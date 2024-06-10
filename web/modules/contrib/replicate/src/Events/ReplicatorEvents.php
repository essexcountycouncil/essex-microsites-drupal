<?php

namespace Drupal\replicate\Events;

final class ReplicatorEvents {

  /**
   * @see \Drupal\replicate\Events\AfterSaveEvent
   */
  const AFTER_SAVE = 'replicate__after_save';

  /**
   * @see \Drupal\replicate\Events\ReplicateAlterEvent
   */
  const REPLICATE_ALTER = 'replicate__alter';

  /**
   * @param $entity_type_id
   *
   * @return string
   */
  public static function replicateEntityEvent($entity_type_id) {
    return 'replicate__entity__' . $entity_type_id;
  }

  public static function replicateEntityField($field_type) {
    return 'replicate__entity_field__' . $field_type;
  }

}
