<?php

namespace Drupal\localgov_microsites_group_term_ui\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides specific access control for group taxonomy_term entity types.
 *
 * @EntityReferenceSelection(
 *   id = "group:taxonomy_term",
 *   label = @Translation("Group Taxonomy Term selection"),
 *   entity_types = {"taxonomy_term"},
 *   group = "group",
 *   weight = 1
 * )
 */
class GroupTermSelection extends TermSelection {

  /**
   * Constructs a new GroupTermSelection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\domain\DomainNegotiatorInterface $domainNegotiator
   *   The domain negotiator service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, EntityRepositoryInterface $entity_repository, protected DomainNegotiatorInterface $domainNegotiator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $module_handler, $current_user, $entity_field_manager, $entity_type_bundle_info, $entity_repository);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('domain.negotiator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $options = parent::getReferenceableEntities($match, $match_operator, $limit);

    // Try and load the group.
    $group = $this->getGroupFromDomain();
    if (empty($group)) {
      $group = \Drupal::request()->attributes->get('group');
    }

    // Only show group terms if there's a group.
    if ($group instanceof GroupInterface) {
      foreach ($options as $vid => $terms) {
        foreach ($terms as $tid => $name) {
          if (empty($tid) || empty($group->getRelationshipsByEntity(Term::load($tid)))) {
            unset($options[$vid][$tid]);
          }
        }
      }
    }

    return $options;
  }

  /**
   * Retrieves the group entity from the current domain.
   *
   * Copied from trait as currently DefaultSelection::enityRepository isn't
   * type hinted, but GroupFromDomainContextTrait has the property hinted.
   *
   * @see GroupFromDomainContextTrait
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   A group entity if one could be found, NULL otherwise.
   */
  public function getGroupFromDomain(): GroupInterface|null {
    if ($domain = $this->domainNegotiator->getActiveDomain()) {
      if ($uuid = $domain->getThirdPartySetting('group_context_domain', 'group_uuid')) {
        return $this->entityRepository->loadEntityByUuid('group', $uuid);
      }
    }
    return NULL;
  }

}
