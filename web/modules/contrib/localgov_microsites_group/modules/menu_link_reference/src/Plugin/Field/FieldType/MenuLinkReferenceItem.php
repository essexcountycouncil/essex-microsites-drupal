<?php

namespace Drupal\menu_link_reference\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the 'menu_link_reference' field type.
 *
 * @FieldType(
 *   id = "menu_link_reference",
 *   label = @Translation("Menu link reference"),
 *   category = @Translation("General"),
 *   default_widget = "menu_link_reference",
 *   default_formatter = "string",
 *   no_ui = TRUE
 * )
 */
class MenuLinkReferenceItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {}

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {}

}
