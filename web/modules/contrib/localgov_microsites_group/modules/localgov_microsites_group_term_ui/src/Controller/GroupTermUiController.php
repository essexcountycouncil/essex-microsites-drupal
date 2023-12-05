<?php

namespace Drupal\localgov_microsites_group_term_ui\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for group term UI routes.
 */
class GroupTermUiController extends ControllerBase {

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a new GroupNodeController.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $plugin_manager
   *   The group content plugin manager.
   */
  public function __construct(EntityFormBuilderInterface $entity_form_builder, EntityTypeManagerInterface $entity_type_manager, GroupRelationTypeManagerInterface $plugin_manager) {
    $this->entityFormBuilder = $entity_form_builder;
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.form_builder'),
      $container->get('entity_type.manager'),
      $container->get('group_relation_type.manager')
    );
  }

  /**
   * Add group term to vocabulary.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group to add term to.
   * @param string $vid
   *   Vocabulary ID to add term to.
   *
   * @return array
   *   Term create form render array.
   */
  public function addTerm(GroupInterface $group, string $vid) {
    $build = [];

    $term = Term::create([
      'vid' => $vid,
    ]);
    $build['form'] = $this->entityFormBuilder->getForm($term);

    return $build;
  }

  /**
   * Access check for the add term form.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group to add term to.
   * @param string $vid
   *   Vocabulary ID to add term to.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function addTermAccess(AccountInterface $account, GroupInterface $group, string $vid) {

    $plugin_id = 'group_term:' . $vid;
    if ($group->hasPermission('create ' . $plugin_id . ' entity', $account)) {
      return AccessResult::allowed();
    }
    return AccessResult::neutral();
  }

  /**
   * Title for the add term form.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group to add term to.
   * @param string $vid
   *   Vocabulary ID to add term to.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Title for form.
   */
  public function addTermTitle(GroupInterface $group, string $vid) {

    $storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $vocabulary_name = $storage->load($vid)->label();
    return $this->t('Add a %vocabulary term', ['%vocabulary' => $vocabulary_name]);
  }

  /**
   * List all vocabularies enabled for group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group to add term to.
   *
   * @return array
   *   Entity add list render array.
   */
  public function listTaxonomies(GroupInterface $group) {
    $build = ['#theme' => 'entity_add_list', '#bundles' => []];

    // Load all taxonomies enabled for the group.
    $storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $plugin_ids = $this->pluginManager->getInstalledIds($group->getGroupType());
    foreach ($plugin_ids as $plugin_id) {
      if (strpos($plugin_id, 'group_term:') !== 0) {
        continue;
      }
      $plugin = $group->getGroupType()->getPlugin($plugin_id);
      $vocabulary = $storage->load($plugin->getRelationType()->getEntityBundle());

      // Check current user has a permission to view this taxonomy.
      if (!$group->hasPermission('view ' . $plugin_id . ' entity', $this->currentUser())) {
        continue;
      }

      $build['#bundles'][$vocabulary->id()] = [
        'add_link' => Link::createFromRoute($vocabulary->label(), 'view.lgms_group_taxonomy_terms.page',
          [
            'group' => $group->id(),
            'vid' => $vocabulary->id(),
          ]),
        'description' => $vocabulary->getDescription(),
      ];
    }

    return $build;
  }

  /**
   * Access check for the list vocabularies route.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group to add term to.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function listTaxonomiesAccess(AccountInterface $account, GroupInterface $group) {

    if ($group->hasPermission('access group_term overview', $account)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
