<?php

namespace Drupal\group_content_menu\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\group\Entity\GroupRelationship;
use Drupal\group_content_menu\GroupContentMenuInterface;

/**
 * Defines the group content menu entity class.
 *
 * @ContentEntityType(
 *   id = "group_content_menu",
 *   label = @Translation("Group content menu"),
 *   label_collection = @Translation("Group content menus"),
 *   bundle_label = @Translation("Group content menu type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\group_content_menu\GroupContentMenuListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\group_content_menu\Form\GroupContentMenuForm",
 *       "edit" = "Drupal\group_content_menu\Form\GroupContentMenuForm",
 *       "delete" = "Drupal\group_content_menu\Form\GroupContentMenuDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\group_content_menu\Routing\GroupContentMenuRouteProvider",
 *     }
 *   },
 *   base_table = "group_content_menu",
 *   data_table = "group_content_menu_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "bundle" = "bundle",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/group/{group}/menu/add/{plugin_id}",
 *     "add-page" = "/group/{group}/menu/add",
 *     "add-menu-link" = "/group/{group}/menu/{group_content_menu}/add-link",
 *     "edit-menu-link" = "/group/{group}/menu/{group_content_menu}/link/{menu_link_content}",
 *     "delete-menu-link" = "/group/{group}/menu/{group_content_menu}/link/{menu_link_content}/delete",
 *     "canonical" = "/group/{group}/menu/{group_content_menu}",
 *     "edit-form" = "/group/{group}/menu/{group_content_menu}/edit",
 *     "delete-form" = "/group/{group}/menu/{group_content_menu}/delete",
 *     "collection" = "/group/{group}/menus"
 *   },
 *   bundle_entity_type = "group_content_menu_type",
 *   field_ui_base_route = "entity.group_content_menu_type.edit_form"
 * )
 */
class GroupContentMenu extends ContentEntityBase implements GroupContentMenuInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($entities as $entity) {
      $menu_link_manager->deleteLinksInMenu(GroupContentMenuInterface::MENU_PREFIX . $entity->id());

    }
    // Remove any group contents related to this menu before removing the menu.
    if ($entity instanceof ContentEntityInterface) {
      if ($group_relationships = GroupRelationship::loadByEntity($entity)) {
        foreach ($group_relationships as $group_relationship) {
          $group_relationship->delete();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    /** @var \Drupal\group\Entity\GroupRelationshipInterface[] $group_relationships */
    $group_relationships = \Drupal::entityTypeManager()->getStorage('group_relationship')->loadByEntity($this);
    if ($group_relationship = reset($group_relationships)) {
      // The group is needed as a route parameter.
      $uri_route_parameters['group'] = $group_relationship->getGroup()->id();
      $uri_route_parameters['group_content_menu_type'] = $group_relationship->getEntity()->bundle();
    }

    return $uri_route_parameters;
  }

}
