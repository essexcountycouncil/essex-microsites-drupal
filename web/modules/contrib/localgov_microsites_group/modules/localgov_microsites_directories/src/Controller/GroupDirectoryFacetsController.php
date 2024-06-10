<?php

namespace Drupal\localgov_microsites_directories\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacets;
use Drupal\localgov_directories\Entity\LocalgovDirectoriesFacetsType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for directory facets routes.
 */
class GroupDirectoryFacetsController extends ControllerBase {

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
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new GroupContentController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, RouteMatchInterface $route_match) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('current_route_match')
    );
  }

  /**
   * Provides the directory facet creation form.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group content to.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function add(GroupInterface $group) {
    $build = [];

    $directory_facet_type = $this->routeMatch->getParameter('localgov_directories_facets_type');
    if (!is_null($directory_facet_type)) {
      $directory_facet = LocalgovDirectoriesFacets::create(['bundle' => $directory_facet_type]);
      $build['form'] = $this->entityFormBuilder->getForm($directory_facet, 'add');
    }

    return $build;
  }

  /**
   * Title for the add form.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Title for form.
   */
  public function addTitle() {

    $directory_facet_type_id = $this->routeMatch->getParameter('localgov_directories_facets_type');
    $directory_facet_type = LocalgovDirectoriesFacetsType::load($directory_facet_type_id);
    return $this->t('Create a %facet_type directory facet', ['%facet_type' => $directory_facet_type->label()]);
  }

}
