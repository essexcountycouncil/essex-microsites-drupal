<?php

namespace Drupal\localgov_microsites_directories\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\group\Entity\GroupInterface;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacetsType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for directory facets routes.
 */
class GroupDirectoryFacetTypeController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * Constructs a new GroupContentController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The renderer.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * Provides the directory facet type creation form.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group content to.
   *
   * @return array
   *   The form render array.
   */
  public function add(GroupInterface $group) {
    $build = [];

    $directory_facet_type = LocalgovDirectoriesFacetsType::create();
    $build['form'] = $this->entityFormBuilder->getForm($directory_facet_type, 'add');

    return $build;
  }

  /**
   * List all directory facet types for group.
   */
  public function list(GroupInterface $group) {
    $build = ['#theme' => 'entity_add_list', '#bundles' => []];

    $directory_facet_types = $this->entityTypeManager->getStorage('localgov_directories_facets_type')->loadMultiple();
    foreach ($directory_facet_types as $type_name => $type) {
      $label = $type->label();
      $build['#bundles'][$type_name] = [
        'label' => $label,
        'description' => NULL,
        'add_link' => Link::createFromRoute($label, 'view.lgms_group_directory_facets.page',
          [
            'group' => $group->id(),
            'localgov_directories_facets_type' => $type->id(),
          ]),
      ];
    }

    return $build;
  }

}
