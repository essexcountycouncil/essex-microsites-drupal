<?php

namespace Drupal\replicate_ui\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\replicate_ui\Form\ReplicateConfirmForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements hook_entity_type_build().
 *
 * @package Drupal\replicate_ui\Hooks
 */
class EntityTypeBuild implements ContainerInjectionInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new EntityTypeBuild instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Helper function for hook_entity_type_build().
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The entity type.
   */
  public function build(array $entity_types) {
    $entity_types_with_replicate = (array) $this->configFactory->get('replicate_ui.settings')->get('entity_types');
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface && $entity_type->hasLinkTemplate('canonical') && in_array($entity_type_id, $entity_types_with_replicate, TRUE)) {
        $entity_type->setFormClass('replicate', ReplicateConfirmForm::class);
        $entity_type->setLinkTemplate('replicate', $entity_type->getLinkTemplate('canonical') . '/replicate');
      }
    }
  }

}
