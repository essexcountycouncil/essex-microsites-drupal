<?php

namespace Drupal\domain_source;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\domain\DomainElementManager;

/**
 * Handles hidden Domain Source form options.
 */
class DomainSourceElementManager extends DomainElementManager implements DomainSourceElementManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function disallowedOptions(FormStateInterface $form_state, array $field) {
    $options = [];
    $object = $form_state->getFormObject();
    if ($object instanceof EntityFormInterface) {
      /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
      $entity = $object->getEntity();
      $entity_values = $entity->get(DomainSourceElementManagerInterface::DOMAIN_SOURCE_FIELD)->offsetGet(0);
      if (isset($field['widget']['#options']) && !empty($entity_values)) {
        $value = $entity_values->getValue('target_id');
        $options = array_diff_key(array_flip($value), $field['widget']['#options']);
      }
    }
    return array_keys($options);
  }

}
