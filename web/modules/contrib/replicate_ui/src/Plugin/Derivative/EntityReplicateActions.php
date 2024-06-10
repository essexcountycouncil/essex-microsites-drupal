<?php

namespace Drupal\replicate_ui\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityReplicateActions extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates a new RouteSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $config = $this->configFactory->get('replicate_ui.settings');
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface && in_array($entity_type_id, $config->get('entity_types')) && $entity_type->hasLinkTemplate('canonical')) {
        $replicate_route_name = "entity.$entity_type_id.replicate";
        // Allow for exporting in things such as VBO by ensuring that the action
        // names will pass validation in ConfigBase.
        $safe_action_name = str_replace('.', '_', $replicate_route_name);

        $this->derivatives[$safe_action_name] = [
          'label' => $this->t('Delete @entity_type', ['@entity_type' => $entity_type->getLabel()]),
          'type' => $entity_type_id,
          'config_form_route_name' => "entity.$entity_type_id.replicate",
          ] + $base_plugin_definition;
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
