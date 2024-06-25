<?php

namespace Drupal\group_content_menu;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper class to handle altering node forms.
 *
 * @package Drupal\GroupContentMenu
 */
class NodeFormAlter implements ContainerInjectionInterface {

  /**
   * The `entity_type.manager` service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The `menu.parent_form_selector` service.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuParentSelector;

  /**
   * The `current_user` service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Construct our class and inject dependencies.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The `entity_type.manager` service.
   * @param \Drupal\Core\Menu\MenuParentFormSelectorInterface $menu_parent_form_selector
   *   The `menu.parent_form_selector` service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The `current_user` service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MenuParentFormSelectorInterface $menu_parent_form_selector, AccountProxyInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->menuParentSelector = $menu_parent_form_selector;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('menu.parent_form_selector'),
      $container->get('current_user'),
    );
  }

  /**
   * Alter node forms to add GroupContentMenu options where appropriate.
   *
   * @param array $form
   *   A form array as from hook_form_alter().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A form state object as from hook_form_alter().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see group_content_menu_form_node_form_alter()
   */
  public function alter(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $form_state->getFormObject()->getEntity();

    $groups = $this->getGroups($form_state, $node);
    if (empty($groups)) {
      return;
    }

    $group_menus = $this->getGroupMenus($groups);
    if (empty($group_menus)) {
      return;
    }

    if ($this->menuExistsAndAccessible($form)) {
      // The menu element exists and is accessible, update the menu element with
      // the group menus options.
      $this->updateMenuElement($form, $node, $group_menus);
    }
    else {
      // Build menu form out only if menu has explicitly not had access denied.
      if (!isset($form['#access']) || $form['#access'] !== FALSE) {
        $this->attachMenuElement($form, $node, $group_menus);
      }
    }
  }

  /**
   * Build a menu element for a node form with only group menu options.
   *
   * @param array $form
   *   The node form to add the menu element to.
   * @param \Drupal\node\NodeInterface $node
   *   The current node being edited.
   * @param array $group_menus
   *   The group menus available as options.
   */
  protected function attachMenuElement(array &$form, NodeInterface $node, array $group_menus): void {
    $defaults = group_content_menu_get_menu_link_default($node, array_keys($group_menus));
    $default_parent = $defaults['menu_name'] . ':' . $defaults['parent'];

    $form['menu'] = [
      '#type' => 'details',
      '#title' => t('Menu settings'),
      '#open' => (bool) $defaults['id'],
      '#group' => 'advanced',
      '#attached' => [
        'library' => ['menu_ui/drupal.menu_ui'],
      ],
      '#tree' => TRUE,
      '#weight' => -2,
      '#attributes' => ['class' => ['menu-link-form']],
    ];
    $form['menu']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Provide a menu link'),
      '#default_value' => (int) (bool) $defaults['id'],
    ];
    $form['menu']['link'] = [
      '#type' => 'container',
      '#parents' => ['menu'],
      '#states' => [
        'invisible' => [
          'input[name="menu[enabled]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    // Populate the element with the link data.
    foreach (['id', 'entity_id'] as $key) {
      $form['menu']['link'][$key] = [
        '#type' => 'value',
        '#value' => $defaults[$key],
      ];
    }

    $form['menu']['link']['title'] = [
      '#type' => 'textfield',
      '#title' => t('Menu link title'),
      '#default_value' => $defaults['title'],
      '#maxlength' => $defaults['title_max_length'],
    ];

    $form['menu']['link']['description'] = [
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#default_value' => $defaults['description'],
      '#description' => t('Shown when hovering over the menu link.'),
      '#maxlength' => $defaults['description_max_length'],
    ];

    $form['menu']['link']['menu_parent'] = $this->menuParentSelector
      ->parentSelectElement($default_parent, $defaults['id'], $group_menus);
    $form['menu']['link']['menu_parent']['#title'] = t('Parent link');
    $form['menu']['link']['menu_parent']['#attributes']['class'][] = 'menu-parent-select';

    $form['menu']['link']['weight'] = [
      '#type' => 'number',
      '#title' => t('Weight'),
      '#default_value' => $defaults['weight'],
      '#description' => t('Menu links with lower weights are displayed before links with higher weights.'),
    ];

    foreach (array_keys($form['actions']) as $action) {
      if ($action !== 'preview' && isset($form['actions'][$action]['#type']) && $form['actions'][$action]['#type'] === 'submit') {
        $form['actions'][$action]['#submit'][] = 'menu_ui_form_node_form_submit';
      }
    }

    $form['#entity_builders'][] = 'menu_ui_node_builder';
  }

  /**
   * Update an existing menu element with group menu options.
   *
   * @param array $form
   *   The node form to add the menu element to.
   * @param \Drupal\node\NodeInterface $node
   *   The current node being edited.
   * @param array $group_menus
   *   The group menus available as options.
   */
  protected function updateMenuElement(array &$form, NodeInterface $node, array $group_menus): void {
    // Check for default menu link values from the traditional menu system.
    $defaults = menu_ui_get_menu_link_defaults($node);
    if (empty($defaults['id'])) {
      // Node does not have a menu link in traditional menus, check group menus.
      $defaults = group_content_menu_get_menu_link_default($node, array_keys($group_menus));
      if ($defaults['id']) {
        $has_link_in_group_menus = TRUE;
      }
    }

    $group_menu_parent_options = $this->menuParentSelector
      ->getParentSelectOptions($defaults['id'], $group_menus);

    $traditional_menu_parent_options = $form['menu']['link']['menu_parent']['#options'];
    $form['menu']['link']['menu_parent']['#options'] = $group_menu_parent_options + $traditional_menu_parent_options;

    if (!empty($has_link_in_group_menus)) {
      $form['menu']['#open'] = (bool) $defaults['id'];
      $form['menu']['enabled']['#default_value'] = (int) (bool) $defaults['id'];

      $form['menu']['link']['id']['#value'] = $defaults['id'];
      $form['menu']['link']['entity_id']['#value'] = $defaults['entity_id'];

      $form['menu']['link']['title']['#default_value'] = $defaults['title'];
      $form['menu']['link']['title']['#maxlength'] = $defaults['title_max_length'];

      $form['menu']['link']['description']['#default_value'] = $defaults['description'];
      $form['menu']['link']['description']['#maxlength'] = $defaults['description_max_length'];

      $form['menu']['link']['menu_parent']['#default_value'] = $defaults['menu_name'] . ':' . $defaults['parent'];

      $form['menu']['link']['weight']['#default_value'] = $defaults['weight'];
    }
  }

  /**
   * Get a node's groups for the edit form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object from hook_form_node_form_alter().
   * @param \Drupal\node\NodeInterface $node
   *   The node object being edited.
   *
   * @return array|\Drupal\group\Entity\GroupInterface[]
   *   An empty array or an array of Groups.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getGroups(FormStateInterface $form_state, NodeInterface $node): array {
    $groups = [];

    if ($group_id = $form_state->get('group')) {
      // If group is set in $form_state, we're currently creating a new node in
      // a group so the node will have only one group.
      $groups[] = $group_id;
    }
    elseif (!$node->isNew()) {
      // We're on a node's edit form. A node can be added to any number of
      // groups so we must load all groups for the node.
      $group_relationships = $this->entityTypeManager
        ->getStorage('group_relationship')
        ->loadByEntity($node);
      $group_ids = array_map(static function (GroupRelationshipInterface $group_relationship) {
        return $group_relationship->getGroup()->id();
      }, $group_relationships);
      $groups = $this->entityTypeManager
        ->getStorage('group')
        ->loadMultiple($group_ids);
    }

    return $groups;
  }

  /**
   * Get an array of GroupContentMenus keyed by mlid.
   *
   * @param \Drupal\group\Entity\GroupInterface[] $groups
   *   An array of groups to get menus for.
   *
   * @return array
   *   An array of GroupContentMenu labels, keyed by menu link id.
   */
  protected function getGroupMenus(array $groups): array {
    $menus = [];
    foreach ($groups as $group) {
      if ($this->canManageGroupMenuItems($group, $this->currentUser)) {
        foreach (group_content_menu_get_menus_per_group($group) as $group_content) {
          $group_menu = $group_content->getEntity();
          $mlid = GroupContentMenuInterface::MENU_PREFIX . $group_menu->id();
          $menus[$mlid] = $group_menu->label() . " ({$group->label()})";
        }
      }
    }

    return array_unique($menus);
  }

  /**
   * Check if a user can manage menu items within a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to check for.
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The user to access check.
   *
   * @return bool
   *   TRUE if the user can manage menu items within the group.
   */
  protected function canManageGroupMenuItems(GroupInterface $group, AccountProxyInterface $user): bool {
    if ($user->hasPermission('administer menu')) {
      return TRUE;
    }
    if ($group->hasPermission('manage group_content_menu menu items', $user)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Check if menu exists and is accessible on node form.
   *
   * @param array $form
   *   The node form render array.
   *
   * @return bool
   *   True if the menu exists and is accessible, false otherwise.
   */
  protected function menuExistsAndAccessible(array $form): bool {
    return !empty($form['menu']) && (
        !isset($form['menu']['#access']) || $form['menu']['#access'] === TRUE
      );
  }

}
