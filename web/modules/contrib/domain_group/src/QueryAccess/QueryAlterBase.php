<?php

namespace Drupal\domain_group\QueryAccess;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a class for altering entity queries.
 *
 * Extends entity queries to ensure group is always joined - it isn't if a user
 * has 'by pass group' access in group module. This is then used to limit access
 * by group domain.
 *
 * @see Drupal\group\QueryAccess\EntityQueryAlter
 *
 * @internal
 */
abstract class QueryAlterBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The domain negotiator service.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * The query cacheable metadata.
   *
   * @var \Drupal\Core\Cache\CacheableMetadata
   */
  protected $cacheableMetadata;

  /**
   * The query to alter.
   *
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $query;

  /**
   * The entity type to alter the query for.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The base table alias.
   *
   * @var string|false
   */
  protected $baseTableAlias = FALSE;

  /**
   * Constructs a new EntityQueryAlter object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $plugin_manager
   *   The group relation type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   The domain negotiator.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupRelationTypeManagerInterface $plugin_manager, Connection $database, RendererInterface $renderer, DomainNegotiatorInterface $domain_negotiator, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManager = $plugin_manager;
    $this->database = $database;
    $this->renderer = $renderer;
    $this->domainNegotiator = $domain_negotiator;
    $this->requestStack = $request_stack;
    $this->cacheableMetadata = new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('group_relation_type.manager'),
      $container->get('database'),
      $container->get('renderer'),
      $container->get('domain.negotiator'),
      $container->get('request_stack'),
    );
  }

  /**
   * Alters the select query for the given entity type.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The select query.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   */
  public function alter(SelectInterface $query, EntityTypeInterface $entity_type) {
    $this->query = $query;
    $this->entityType = $entity_type;
    if ($group_id = $this->groupDomainActive()) {
      $this->doAlter($group_id);
    }
    $this->applyCacheability();
  }

  /**
   * Actually alters the select query for the given entity type.
   *
   * @param int $group_id
   *   Active group.
   */
  abstract protected function doAlter($group_id);

  protected function groupDomainActive() {
    $domain_group_config = \Drupal::config('domain_group.settings');
    if (!$domain_group_config->get('unique_group_access')) {
      // @todo everywhere? add config to $this->cacheableMetadata->addCacheTags($cache_tags);
      $this->cacheableMetadata->addCacheableDependency($domain_group_config);
      return NULL;
    }

    $active = $this->domainNegotiator->getActiveDomain();
    if (empty($active)) {
      return NULL;
    }
    $group_id = $active->getThirdPartySetting('domain_group', 'group');
    if (empty($group_id)) {
      return NULL;
    }

    return $group_id;
  }

  /**
   * Find the alias of the base table.
   *
   * @return string
   *   The base table alias.
   */
  protected function getBaseTableAlias() {
    if ($this->baseTableAlias === FALSE) {
      foreach ($this->query->getTables() as $alias => $table) {
        if ($table['join type'] === NULL) {
          $this->baseTableAlias = $alias;
          break;
        }
      }
    }

    return $this->baseTableAlias;
  }

  /**
   * Applies the cacheablity metadata to the current request.
   */
  protected function applyCacheability() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->isMethodCacheable() && $this->renderer->hasRenderContext() && $this->hasCacheableMetadata()) {
      $build = [];
      $this->cacheableMetadata->applyTo($build);
      $this->renderer->render($build);
    }
  }

  /**
   * Check if the cacheable metadata is not empty.
   *
   * An empty cacheable metadata object has no context, tags, and is permanent.
   *
   * @return bool
   *   TRUE if there is cacheability metadata, otherwise FALSE.
   */
  protected function hasCacheableMetadata() {
    return $this->cacheableMetadata->getCacheMaxAge() !== Cache::PERMANENT
      || count($this->cacheableMetadata->getCacheContexts()) > 0
      || count($this->cacheableMetadata->getCacheTags()) > 0;
  }

}
