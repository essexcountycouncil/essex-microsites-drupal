<?php

namespace Drupal\localgov_microsites_group\Plugin\Group\RelationHandlerDefault;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Plugin\Group\RelationHandler\UiTextProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\UiTextProviderTrait;
use Drupal\group\Plugin\Group\RelationHandlerDefault\UiTextProvider as GroupDefaultUiTextProvider;

/**
 * Provides UI text for group relations.
 */
class UiTextProvider implements UiTextProviderInterface {

  use UiTextProviderTrait;

  /**
   * Constructs a new UiTextProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandlerDefault\UiTextProvider $parent
   *   The parent.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(GroupDefaultUiTextProvider $parent, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->parent = $parent;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddPageLabel($create_mode) {
    if ($bundle = $this->groupRelationType->getEntityBundle()) {
      $storage = $this->entityTypeManager()->getStorage($this->entityType->getBundleEntityType());
      $t_args['@bundle'] = $storage->load($bundle)->label();
      return $this->t('@bundle', $t_args);
    }

    return $this->parent->getAddPageLabel($create_mode);
  }

  /**
   * {@inheritdoc}
   */
  public function getAddPageDescription($create_mode) {
    $t_args = ['%entity_type' => $this->entityType->getSingularLabel()];

    if ($bundle = $this->groupRelationType->getEntityBundle()) {
      $storage = $this->entityTypeManager()->getStorage($this->entityType->getBundleEntityType());
      $bundle_entity = $storage->load($bundle);
      if (method_exists($bundle_entity, 'getDescription')) {
        return $bundle_entity->getDescription();
      }
      else {
        $t_args['%bundle'] = $bundle_entity->label();
        return $create_mode
          ? $this->t('Add new %entity_type of type %bundle.', $t_args)
          : $this->t('Add existing %entity_type of type %bundle.', $t_args);
      }
    }

    return $create_mode
      ? $this->t('Add new %entity_type.', $t_args)
      : $this->t('Add existing %entity_type.', $t_args);
  }

  /**
   * {@inheritdoc}
   */
  public function getAddFormTitle($create_mode) {
    return $this->t('Add @name', ['@name' => $this->groupRelationType->getLabel()]);
  }

}
