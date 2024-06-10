<?php

namespace Drupal\localgov_microsites_group\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\group\Entity\Storage\GroupRelationshipStorageInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\Query\QueryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds entity access checks for domain groups.
 *
 * @SearchApiProcessor(
 *   id = "domain_group_entity_access",
 *   label = @Translation("Domain group access"),
 *   description = @Translation("Adds entity access checks for domain group."),
 *   stages = {
 *     "add_properties" = 0,
 *     "pre_index_save" = -10,
 *     "preprocess_query" = -30,
 *   },
 * )
 */
class EntityAccess extends ProcessorPluginBase {

  use LoggerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The domain negotiator service.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->setLogger($container->get('logger.channel.search_api'));
    $processor->setDomainNegotiator($container->get('domain.negotiator'));
    $processor->setEntityTypeManager($container->get('entity_type.manager'));

    return $processor;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager(): EntityTypeManagerInterface {
    return $this->entityTypeManager ?: \Drupal::entityTypeManager();
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * Retrieves the domain negotiator service.
   *
   * @return \Drupal\domain\DomainNegotiatorInterface
   *   The domain negotiator.
   */
  public function getDomainNegotiator(): DomainNegotiatorInterface {
    return $this->domainNegotiator ?: \Drupal::service('domain.negotiator');
  }

  /**
   * Sets the domain negotiator service.
   *
   * @param \Drupaldomain\DomainNegotiatorInterface $domain_negotiator
   *   The domain negotiator service.
   *
   * @return $this
   */
  public function setDomainNegotiator(DomainNegotiatorInterface $domain_negotiator) {
    $this->domainNegotiator = $domain_negotiator;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    // Group content type plugins can be added to add support for an entity
    // type. If we return false here when they are not yet enabled, they can
    // then later be added. Better to just apply and check the plugins at query
    // time?
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Domain groups'),
        'description' => $this->t('Data needed to apply doman group entity access.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'hidden' => TRUE,
        'is_list' => TRUE,
      ];
      $properties['domain_groups'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $entity = $item->getOriginalObject()->getValue();

    $group_relationship_storage = $this->getEntityTypeManager()->getStorage('group_relationship');
    assert($group_relationship_storage instanceof GroupRelationshipStorageInterface);
    $entity_group_relationship = $group_relationship_storage->loadByEntity($entity);
    if (empty($entity_group_relationship)) {
      return;
    }

    $fields = $item->getFields();
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($fields, NULL, 'domain_groups');
    foreach ($fields as $field) {
      foreach ($entity_group_relationship as $group_relationship) {
        $field->addValue($group_relationship->getGroup()->uuid());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    $field = $this->ensureField(NULL, 'domain_groups', 'string');
    $field->setHidden();
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {
    if (!$query->getOption('search_api_bypass_access')) {
      $this->addDomainGroupAccess($query);
    }
  }

  /**
   * Adds an entity access filter to a search query, if applicable.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query to which a node access filter should be added, if applicable.
   */
  protected function addDomainGroupAccess(QueryInterface $query): void {
    $active = $this->domainNegotiator->getActiveDomain();
    if (empty($active)) {
      return;
    }
    $group_uuid = $active->getThirdPartySetting('group_context_domain', 'group_uuid');
    if (empty($group_uuid)) {
      return;
    }

    // Filter by the domain group.
    $domain_groups_field = $this->findField(NULL, 'domain_groups', 'string');
    if (!$domain_groups_field) {
      return;
    }
    $domain_groups_field_id = $domain_groups_field->getFieldIdentifier();
    $query->addCondition($domain_groups_field_id, $group_uuid, 'IN');
  }

}
