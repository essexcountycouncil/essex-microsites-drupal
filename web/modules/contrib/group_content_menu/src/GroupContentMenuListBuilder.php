<?php

namespace Drupal\group_content_menu;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the group content menu entity type.
 */
class GroupContentMenuListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new GroupContentMenuListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $group = \Drupal::routeMatch()->getParameter('group');
    if ($group) {
      return array_map(static function (GroupRelationshipInterface $group_relationship) {
        return $group_relationship->getEntity();
      }, group_content_menu_get_menus_per_group($group));
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['bundle'] = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\group_content_menu\GroupContentMenuInterface */
    $row['id'] = $entity->toLink($entity->label(), 'edit-form')->toString();
    $row['bundle'] = \Drupal::entityTypeManager()->getStorage('group_content_menu_type')->load($entity->bundle())->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    /** @var \Drupal\group\Entity\GroupRelationshipInterface[] $group_relationships */
    $group_relationships = \Drupal::entityTypeManager()->getStorage('group_relationship')->loadByEntity($entity);
    if ($group_relationship = reset($group_relationships)) {
      $entity_type_id = $entity->getEntityTypeId();
      $account = \Drupal::currentUser()->getAccount();
      if ($entity->hasLinkTemplate('edit-form') && $group_relationship->getGroup()->hasPermission("update own group_content_menu:$entity_type_id entity", $account)) {
        $operations['edit'] = [
          'title' => $this->t('Edit'),
          'weight' => 10,
          'url' => $this->ensureDestination($entity->toUrl('edit-form')),
        ];
      }
      if ($entity->hasLinkTemplate('delete-form') && $group_relationship->getGroup()->hasPermission("delete own group_content_menu:$entity_type_id entity", $account)) {
        $operations['delete'] = [
          'title' => $this->t('Delete'),
          'weight' => 100,
          'url' => $this->ensureDestination($entity->toUrl('delete-form')),
        ];
      }
    }

    return $operations;
  }

}
