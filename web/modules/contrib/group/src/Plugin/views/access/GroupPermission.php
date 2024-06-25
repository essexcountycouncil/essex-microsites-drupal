<?php

namespace Drupal\group\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupPermissionHandlerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides group permission-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "group_permission",
 *   title = @Translation("Group permission"),
 *   help = @Translation("Access will be granted to users with the specified group permission string.")
 * )
 */
class GroupPermission extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * The group permission handler.
   *
   * @var \Drupal\group\Access\GroupPermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The group entity from the route.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * The group context from the route.
   *
   * @var \Drupal\Core\Plugin\Context\ContextInterface
   */
  protected $context;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Permission object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\group\Access\GroupPermissionHandlerInterface $permission_handler
   *   The group permission handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $context_provider
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The group route context.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GroupPermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler, ContextProviderInterface $context_provider, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->permissionHandler = $permission_handler;
    $this->moduleHandler = $module_handler;

    $contexts = $context_provider->getRuntimeContexts(['group']);
    $this->context = $contexts['group'];
    $this->group = $this->context->getContextValue();
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('group.permissions'),
      $container->get('module_handler'),
      $container->get('group.group_route_context'),
      $container->get('entity_type.manager')
    );
  }

  public function access(AccountInterface $account) {
    // If group is not in the current context, try to get it from view
    // arguments instead.
    if (!$this->group instanceof GroupInterface) {
      $group_id = 0;
      $path = $this->displayHandler->getOption('path');
      if (!empty($path)) {
        $group_arg_index = 0;
        $parts = explode('/', $path);
        foreach ($parts as $part) {
          if (str_starts_with($part, '%')) {
            if ($part === '%group') {
              $group_id = $this->view->args[$group_arg_index] ?? 0;
              break;
            }
            $group_arg_index++;
          }
        }
      }

      if (empty($group_id)) {
        foreach (array_values($this->displayHandler->getOption('arguments')) as $id => $arg) {
          if ($arg['plugin_id'] == 'group_id') {
            $group_id = $this->view->args[$id];
          }
        }
      }

      if ($group_id !== 0) {
        $this->group = $this->entityTypeManager->getStorage('group')->load($group_id);
      }
    }

    if ($this->group instanceof GroupInterface) {
      return $this->group->hasPermission($this->options['group_permission'], $account);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_group_permission', $this->options['group_permission']);

    // Upcast any %group path key the user may have configured so the
    // '_group_permission' access check will receive a properly loaded group.
    $route->setOption('parameters', ['group' => ['type' => 'entity:group']]);
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    $permissions = $this->permissionHandler->getPermissions(TRUE);
    if (isset($permissions[$this->options['group_permission']])) {
      return $permissions[$this->options['group_permission']]['title'];
    }

    return $this->t($this->options['group_permission']);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['group_permission'] = ['default' => 'view group'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Get list of permissions.
    $permissions = [];
    foreach ($this->permissionHandler->getPermissions(TRUE) as $permission_name => $permission) {
      $display_name = $this->moduleHandler->getName($permission['provider']);
      $permissions[$display_name . ' : ' . $permission['section']][$permission_name] = strip_tags($permission['title']);
    }

    $form['group_permission'] = [
      '#type' => 'select',
      '#options' => $permissions,
      '#title' => $this->t('Group permission'),
      '#default_value' => $this->options['group_permission'],
      '#description' => $this->t('Only users with the selected group permission will be able to access this display.<br /><strong>Warning:</strong> This will only work if there is a {group} parameter in the route. If not, it will always deny access.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::mergeMaxAges(Cache::PERMANENT, $this->context->getCacheMaxAge());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(['user.group_permissions'], $this->context->getCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->context->getCacheTags();
  }

}
