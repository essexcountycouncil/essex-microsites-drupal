<?php

namespace Drupal\group_content_menu\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Group menu instance edit forms.
 *
 * @ingroup group_content_menu
 */
class GroupContentMenuForm extends ContentEntityForm {

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The menu tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The overview tree form.
   *
   * @var array
   */
  protected $overviewTreeForm = ['#tree' => TRUE];

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\group_content_menu\Entity\GroupContentMenu
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->menuLinkManager = $container->get('plugin.manager.menu.link');
    $instance->menuTree = $container->get('menu.link_tree');
    $instance->linkGenerator = $container->get('link_generator');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    // On entity add, no links are attached yet, so bail out here.
    if ($this->entity->isNew()) {
      return $form;
    }
    $group = $form_state->get('group');
    if (!$group) {
      /** @var \Drupal\group\Entity\GroupRelationshipInterface[] $group_relationships */
      $group_relationships = $this->entityTypeManager->getStorage('group_relationship')->loadByEntity($this->entity);
      // If no related group content, nothing to do. Bail early.
      if (!$group_relationships) {
        return $form;
      }
      $group_relationship = reset($group_relationships);
      $group = $group_relationship->getGroup();
    }
    $form_state->set('group', $group);

    // Ensure that menu_overview_form_submit() knows the parents of this form
    // section.
    if (!$form_state->has('menu_overview_form_parents')) {
      $form_state->set('menu_overview_form_parents', []);
    }

    $form['#attached']['library'][] = 'menu_ui/drupal.menu_ui.adminforms';

    $tree = $this->menuTree->load(GroupContentMenuInterface::MENU_PREFIX . $this->entity->id(), new MenuTreeParameters());

    // We indicate that a menu administrator is running the menu access check.
    $this->getRequest()->attributes->set('_menu_admin', TRUE);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    $this->getRequest()->attributes->set('_menu_admin', FALSE);

    // Determine the delta; the number of weights to be made available.
    $count = static function (array $tree) {
      $sum = static function ($carry, MenuLinkTreeElement $item) {
        return $carry + $item->count();
      };
      return array_reduce($tree, $sum);
    };
    $delta = max($count($tree), 50);

    $form['links'] = [
      '#type' => 'table',
      '#theme' => 'table__menu_overview',
      '#header' => [
        $this->t('Menu link'),
        [
          'data' => $this->t('Enabled'),
          'class' => ['checkbox'],
        ],
        $this->t('Weight'),
        [
          'data' => $this->t('Operations'),
          'colspan' => 3,
        ],
      ],
      '#attributes' => [
        'id' => 'menu-overview',
      ],
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'menu-parent',
          'subgroup' => 'menu-parent',
          'source' => 'menu-id',
          'hidden' => TRUE,
          'limit' => $this->menuTree->maxDepth() - 1,
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'menu-weight',
        ],
      ],
    ];

    // Check if the user has the global permission to add new links to the menu
    // instance, or has this permission inside the group.
    $permission = 'administer group content menu types';
    $plugin_id = 'group_content_menu:' . $this->entity->getEntityTypeId();
    $has_permission = $this->currentUser()->hasPermission($permission) || $group->hasPermission("view $plugin_id entity", $this->currentUser());

    // Supply the empty text.
    if ($has_permission) {
      $form['links']['#empty'] = $this->t('There are no menu links yet. <a href=":url">Add link</a>.', [
        ':url' => $this->entity->toUrl('add-menu-link', [
          'query' => ['destination' => $this->entity->toUrl('edit-form')->toString()],
        ])->toString(),
      ]);
    }
    else {
      $form['links']['#empty'] = $this->t('There are no menu links yet.');
    }

    $links = $this->buildOverviewTreeForm($tree, $delta, $group);
    foreach (Element::children($links) as $id) {
      if (isset($links[$id]['#item'])) {
        $element = $links[$id];

        $form['links'][$id]['#item'] = $element['#item'];

        // TableDrag: Mark the table row as draggable.
        $form['links'][$id]['#attributes'] = $element['#attributes'];
        $form['links'][$id]['#attributes']['class'][] = 'draggable';

        // TableDrag: Sort the table row according to its existing/configured
        // weight.
        $form['links'][$id]['#weight'] = $element['#item']->link->getWeight();

        // Add special classes to be used for tabledrag.js.
        $element['parent']['#attributes']['class'] = ['menu-parent'];
        $element['weight']['#attributes']['class'] = ['menu-weight'];
        $element['id']['#attributes']['class'] = ['menu-id'];

        $form['links'][$id]['title'] = [
          [
            '#theme' => 'indentation',
            '#size' => $element['#item']->depth - 1,
          ],
          $element['title'],
        ];
        $form['links'][$id]['enabled'] = $element['enabled'];
        $form['links'][$id]['enabled']['#wrapper_attributes']['class'] = ['checkbox', 'menu-enabled'];

        $form['links'][$id]['weight'] = $element['weight'];

        // Operations (dropbutton) column.
        $form['links'][$id]['operations'] = $element['operations'];

        $form['links'][$id]['id'] = $element['id'];
        $form['links'][$id]['parent'] = $element['parent'];
      }
    }
    return $form;
  }

  /**
   * Build overview tree form.
   */
  protected function buildOverviewTreeForm($tree, $delta, GroupInterface $group) {
    $form = &$this->overviewTreeForm;
    $tree_access_cacheability = new CacheableMetadata();
    foreach ($tree as $element) {
      $tree_access_cacheability = $tree_access_cacheability->merge(CacheableMetadata::createFromObject($element->access));

      // Only render accessible links.
      if (!$element->access->isAllowed()) {
        continue;
      }

      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $element->link;
      if ($link) {
        $id = 'menu_plugin_id:' . $link->getPluginId();
        $form[$id]['#item'] = $element;
        $form[$id]['#attributes'] = $link->isEnabled() ? ['class' => ['menu-enabled']] : ['class' => ['menu-disabled']];
        $form[$id]['title'] = Link::fromTextAndUrl($link->getTitle(), $link->getUrlObject())->toRenderable();
        if (!$link->isEnabled()) {
          $form[$id]['title']['#suffix'] = ' (' . $this->t('disabled') . ')';
        }
        // @todo Remove this in https://www.drupal.org/node/2568785.
        elseif ($id === 'menu_plugin_id:user.logout') {
          $form[$id]['title']['#suffix'] = ' (' . $this->t('<q>Log in</q> for anonymous users') . ')';
        }
        // @todo Remove this in https://www.drupal.org/node/2568785.
        elseif (($url = $link->getUrlObject()) && $url->isRouted() && $url->getRouteName() == 'user.page') {
          $form[$id]['title']['#suffix'] = ' (' . $this->t('logged in users only') . ')';
        }

        $form[$id]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable @title menu link', ['@title' => $link->getTitle()]),
          '#title_display' => 'invisible',
          '#default_value' => $link->isEnabled(),
        ];
        $form[$id]['weight'] = [
          '#type' => 'weight',
          '#delta' => $delta,
          '#default_value' => $link->getWeight(),
          '#title' => $this->t('Weight for @title', ['@title' => $link->getTitle()]),
          '#title_display' => 'invisible',
        ];
        $form[$id]['id'] = [
          '#type' => 'hidden',
          '#value' => $link->getPluginId(),
        ];
        $form[$id]['parent'] = [
          '#type' => 'hidden',
          '#default_value' => $link->getParent(),
        ];
        // Build a list of operations.
        $operations = [];
        $operations['edit'] = [
          'title' => $this->t('Edit'),
        ];
        // Use this module's edit route for the menu. This means we don't have
        // to give elevated menu_ui access to edit menu links.
        $operations['edit']['url'] = Url::fromRoute('entity.group_content_menu.edit_menu_link', [
          'group' => $group->id(),
          'group_content_menu' => $this->entity->id(),
          'menu_link_content' => $link->getMetaData()['entity_id'],
        ]);
        // Bring the user back to the menu overview.
        $operations['edit']['query'] = $this->getRedirectDestination()->getAsArray();

        // Links can either be reset or deleted, not both.
        if ($link->isResettable()) {
          $operations['reset'] = [
            'title' => $this->t('Reset'),
            'url' => Url::fromRoute('menu_ui.link_reset', ['menu_link_plugin' => $link->getPluginId()]),
          ];
        }
        if ($link->isDeletable()) {
          $operations['delete']['url'] = Url::fromRoute('entity.group_content_menu.delete_menu_link', [
            'group' => $group->id(),
            'group_content_menu' => $this->entity->id(),
            'menu_link_content' => $link->getMetaData()['entity_id'],
          ]);
          $operations['delete']['query'] = $this->getRedirectDestination()->getAsArray();
          $operations['delete']['title'] = $this->t('Delete');
        }
        if ($link->isTranslatable()) {
          $operations['translate'] = [
            'title' => $this->t('Translate'),
            'url' => $link->getTranslateRoute(),
          ];
          $operations['translate']['query'] = ['destination' => Url::fromRouteMatch($this->getRouteMatch())->toString()];
        }

        // Only display the operations to which the user has access.
        foreach ($operations as $key => $operation) {
          if (!$operation['url']->access()) {
            unset($operations[$key]);
          }
        }

        $form[$id]['operations'] = [
          '#type' => 'operations',
          '#links' => $operations,
        ];
      }

      if ($element->subtree) {
        $this->buildOverviewTreeForm($element->subtree, $delta, $group);
      }
    }

    $tree_access_cacheability
      ->merge(CacheableMetadata::createFromRenderArray($form))
      ->applyTo($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $menu = $this->getEntity();
    if (!$menu->isNew()) {
      $this->submitOverviewForm($form, $form_state);
    }
    $status = $menu->save();
    $arguments = ['%label' => $this->entity->label()];

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New group menu <em>%label</em> has been created.', $arguments));
      $this->logger('group_content_menu')->notice('Created new group menu %label', $arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The group menu <em>%label</em> has been update.', $arguments));
      $this->logger('group_content_menu')->notice('Updated group menu %label.', $arguments);
    }
  }

  /**
   * Submit handler for the menu overview form.
   *
   * This function takes great care in saving parent items first, then items
   * underneath them. Saving items in the incorrect order can break the tree.
   */
  protected function submitOverviewForm(array $complete_form, FormStateInterface $form_state) {
    // Form API supports constructing and validating self-contained sections
    // within forms, but does not allow to handle the form section's submission
    // equally separated yet. Therefore, we use a $form_state key to point to
    // the parents of the form section.
    $parents = $form_state->get('menu_overview_form_parents');
    $input = NestedArray::getValue($form_state->getUserInput(), $parents);
    $form = &NestedArray::getValue($complete_form, $parents);

    // When dealing with saving menu items, the order in which these items are
    // saved is critical. If a changed child item is saved before its parent,
    // the child item could be saved with an invalid path past its immediate
    // parent. To prevent this, save items in the form in the same order they
    // are sent, ensuring parents are saved first, then their children.
    // See https://www.drupal.org/node/181126#comment-632270.
    $order = is_array($input) ? array_flip(array_keys($input)) : [];
    // Update our original form with the new order.
    $form = array_intersect_key(array_merge($order, $form), $form);

    $fields = ['weight', 'parent', 'enabled'];
    $form_links = $form['links'];
    foreach (Element::children($form_links) as $id) {
      if (isset($form_links[$id]['#item'])) {
        $element = $form_links[$id];
        $updated_values = [];
        // Update any fields that have changed in this menu item.
        foreach ($fields as $field) {
          if ($element[$field]['#value'] !== $element[$field]['#default_value']) {
            $updated_values[$field] = $element[$field]['#value'];
          }
        }
        if ($updated_values) {
          // Use the ID from the actual plugin instance since the hidden value
          // in the form could be tampered with.
          $this->menuLinkManager->updateDefinition($element['#item']->link->getPluginId(), $updated_values);
        }
      }
    }
  }

}
