<?php

namespace Drupal\replicate_ui\Plugin\RulesAction;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\replicate\Replicator;
use Drupal\rules\Core\RulesActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Replicate entity' action.
 *
 * @RulesAction(
 *   id = "replicate_ui_entity_replicate",
 *   label = @Translation("replicate entity"),
 *   category = @Translation("Entity"),
 *   context = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the entity, which should be replicated.")
 *     )
 *   }
 * )
 *
 * @todo: Add access callback information from Drupal 7.
 */
class EntityReplicateAction extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The replicator.
   *
   * @var \Drupal\replicate\Replicator
   */
  protected $replicator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Replicator $replicator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->replicator = $replicator;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('replicate.replicator')
    );
  }

  /**
   * Deletes the Entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The entity to be deleted.
   */
  protected function doExecute(EntityInterface $entity) {
    $this->replicator->replicateEntity($entity);
  }

}
