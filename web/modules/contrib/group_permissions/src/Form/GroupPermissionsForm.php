<?php

namespace Drupal\group_permissions\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupPermissionHandlerInterface;
use Drupal\group\Entity\Group;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Form\GroupPermissionsForm as BasePermissionForm;
use Drupal\group\PermissionScopeInterface;
use Drupal\group_permissions\Entity\GroupPermission;
use Drupal\group_permissions\GroupPermissionsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the group permissions administration form.
 */
class GroupPermissionsForm extends BasePermissionForm {

  /**
   * Group.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * GroupPermission.
   *
   * @var \Drupal\group_permissions\Entity\GroupPermission
   */
  protected $groupPermission;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_permissions';
  }

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * The Current User object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The group permissions manager.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManagerInterface
   */
  protected $groupPermissionsManager;

  /**
   * Constructs a new GroupPermissionsTypeSpecificForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Access\GroupPermissionHandlerInterface $permission_handler
   *   The group permission handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\group_permissions\GroupPermissionsManagerInterface $group_permission_manager
   *   The group permissions manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupPermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler, TimeInterface $time, AccountInterface $current_user, GroupPermissionsManagerInterface $group_permission_manager) {
    parent::__construct($permission_handler, $module_handler);
    $this->entityTypeManager = $entity_type_manager;
    $this->time = $time;
    $this->currentUser = $current_user;
    $this->groupPermissionsManager = $group_permission_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('group.permissions'),
      $container->get('module_handler'),
      $container->get('datetime.time'),
      $container->get('current_user'),
      $container->get('group_permissions.group_permissions_manager')
    );
  }

  /**
   * Gets the group type to build the form for.
   *
   * @return \Drupal\group\Entity\GroupTypeInterface
   *   The group type some or more roles belong to.
   */
  protected function getGroupType() {
    return $this->group->getGroupType();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Group $group = NULL) {

    $this->group = $group;

    $this->groupPermission = $this->groupPermissionsManager->loadByGroup($group);

    if (empty($this->groupPermission)) {
      $this->groupPermission = GroupPermission::create([
        'gid' => $group->id(),
      ]);
    }

    $custom_permissions = $this->groupPermission->getPermissions();

    // Cluster the roles by scope to make the UI easier to understand.
    $role_info = [
      PermissionScopeInterface::OUTSIDER_ID => [],
      PermissionScopeInterface::INSIDER_ID => [],
      PermissionScopeInterface::INDIVIDUAL_ID => [],
    ];

    // Sort the group roles using the static sort() method.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    $group_roles = $this->getGroupRoles();
    uasort($group_roles, '\Drupal\group\Entity\GroupRole::sort');

    // Retrieve information for every role to user further down. We do this to
    // prevent the same methods from being fired (rows * permissions) times.
    foreach ($group_roles as $role_name => $group_role) {
      // Admin roles will not be overridden.
      if ($group_role->isAdmin()) {
        continue;
      }
      $permissions = [];
      if (!$this->groupPermission->isNew()) {
        // Permissions should be explicitly assigned another case we don't
        // provide the permission.
        if (!empty($custom_permissions[$role_name])) {
          $permissions = $custom_permissions[$role_name];
        }
      }
      else {
        $permissions = $group_role->getPermissions();
      }

      $role_info[$group_role->getScope()][$role_name] = [
        'label' => $group_role->label(),
        'global_role' => ($global_role = $group_role->getGlobalRole()) ? $global_role->label() : FALSE,
        'permissions' => $permissions,
        'is_anonymous' => $group_role->isAnonymous(),
        'is_outsider' => $group_role->isOutsider(),
        'is_member' => $group_role->isMember(),
        'is_admin' => $group_role->isAdmin(),
      ];
    }

    // Render the general information.
    if ($info = $this->getInfo()) {
      $form['info'] = $info;
    }

    // Render the link for hiding descriptions.
    $form['system_compact_link'] = [
      '#id' => FALSE,
      '#type' => 'system_compact_link',
    ];

    // Render the roles and permissions table.
    $form['permissions'] = [
      '#type' => 'table',
      '#header' => [$this->t('Permission')],
      '#id' => 'permissions',
      '#attributes' => ['class' => ['permissions', 'js-permissions']],
      '#sticky' => TRUE,
    ];

    // Create a column with header for every group role.
    foreach ($role_info as $scope_id => $scope_info) {
      foreach ($scope_info as $info) {
        $label = $info['label'];
        if ($info['global_role']) {
          $icon = $scope_id === PermissionScopeInterface::OUTSIDER_ID ? '&#128274;' : '&#128273;';
          $label .= '<br />(' . $icon . ' ' . $info['global_role'] . ')';
        }

        $form['permissions']['#header'][] = [
          'data' => ['#markup' => $label],
          'class' => ['checkbox'],
        ];
      }
    }

    // Render the permission as sections of rows.
    $hide_descriptions = system_admin_compact_mode();
    foreach ($this->getPermissions() as $provider => $sections) {
      // Print a full width row containing the provider name for each provider.
      $form['permissions']['module-' . $provider] = [
        [
          '#wrapper_attributes' => [
            'colspan' => count($group_roles) + 1,
            'class' => ['module'],
            'id' => 'module-' . $provider,
          ],
          '#markup' => $this->moduleHandler->getName($provider),
        ],
      ];

      foreach ($sections as $section_id => $permissions) {
        // Start each section with a full width row containing the section name.
        $form['permissions']['section-' . $section_id] = [
          [
            '#wrapper_attributes' => [
              'colspan' => count($group_roles) + 1,
              'class' => ['section'],
              'id' => 'section-' . $section_id,
            ],
            '#markup' => reset($permissions)['section'],
          ],
        ];

        // Then list all permissions for that provider and section.
        foreach ($permissions as $perm => $perm_item) {
          // Create a row for the permission, starting with the description
          // cell.
          $form['permissions'][$perm]['description'] = [
            '#type' => 'inline_template',
            '#template' => '<span class="title">{{ title }}</span>{% if description or warning %}<div class="description">{% if warning %}<em class="permission-warning">{{ warning }}</em><br />{% endif %}{{ description }}</div>{% endif %}',
            '#context' => [
              'title' => $perm_item['title'],
            ],
            '#wrapper_attributes' => [
              'class' => ['permission'],
            ],
          ];

          // Show the permission description and warning if toggled on.
          if (!$hide_descriptions) {
            $form['permissions'][$perm]['description']['#context']['description'] = $perm_item['description'];
            $form['permissions'][$perm]['description']['#context']['warning'] = $perm_item['warning'];
          }

          // Finally build a checkbox cell for every group role.
          foreach ($role_info as $scope_info) {
            foreach ($scope_info as $role_name => $info) {
              $marker_text = FALSE;

              // Show a red '-' if the permission is unavailable.
              $na = $info['is_anonymous'] && !in_array('anonymous', $perm_item['allowed for']);
              $na = $na || ($info['is_outsider'] && !in_array('outsider', $perm_item['allowed for']));
              $na = $na || ($info['is_member'] && !in_array('member', $perm_item['allowed for']));
              if ($na) {
                $marker_text = '&#10060;';
              }

              // Show the special marker if necessary.
              if ($marker_text) {
                $form['permissions'][$perm][$role_name] = [
                  '#title' => $info['label'] . ': ' . $perm_item['title'],
                  '#title_display' => 'invisible',
                  '#wrapper_attributes' => ['class' => ['checkbox']],
                  '#markup' => $marker_text,
                ];
                continue;
              }

              // Show a checkbox if the permissions is available.
              $form['permissions'][$perm][$role_name] = [
                '#title' => $info['label'] . ': ' . $perm_item['title'],
                '#title_display' => 'invisible',
                '#wrapper_attributes' => [
                  'class' => ['checkbox'],
                ],
                '#type' => 'checkbox',
                '#default_value' => in_array($perm, $info['permissions']) ? 1 : 0,
                '#attributes' => [
                  'class' => [
                    'rid-' . $role_name,
                    'js-rid-' . $role_name,
                  ],
                ],
                '#parents' => [$role_name, $perm],
              ];
            }
          }
        }
      }
    }

    $form['revision_log'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Log message'),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Published'),
      '#default_value' => $this->groupPermission->isNew() ? 1 : ($this->groupPermission->isPublished() ? 1 : 0),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save permissions'),
      '#button_type' => 'primary',
    ];

    $form['#attached']['library'][] = 'group/permissions';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getInfo() {
    $list = [
      'role_info' => [
        '#prefix' => '<p>' . $this->t('Group types use three special roles:') . '</p>',
        '#theme' => 'item_list',
        '#items' => [
          ['#markup' => $this->t('<strong>Anonymous:</strong> This is the same as the global Anonymous role, meaning the user has no account.')],
          ['#markup' => $this->t('<strong>Outsider:</strong> This means the user has an account on the site, but is not a member of the group.')],
          ['#markup' => $this->t('<strong>Member:</strong> The default role for anyone in the group. Behaves like the "Authenticated user" role does globally.')],
        ],
      ],
    ];

    return $list + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $permissions = [];
    foreach ($this->getGroupRoles() as $role_name => $group_role) {
      $role_form_value = $form_state->getValue($role_name);
      if (!empty($role_form_value)) {
        /** @var \Drupal\group\Entity\GroupRoleInterface $group_role */
        $permissions[$role_name] = array_keys(array_filter($role_form_value));
      }
    }

    $this->groupPermission->setPermissions($permissions);
    if ($form_state->getValue('status')) {
      $this->groupPermission->setPublished();
    }
    else {
      $this->groupPermission->setUnpublished();
    }

    $violations = $this->groupPermission->validate();
    if (count($violations) == 0) {
      // Set a new Revision.
      $this->groupPermission->setNewRevision();
      $this->groupPermission->setRevisionUserId($this->currentUser->id());
      $this->groupPermission->setRevisionCreationTime($this->time->getRequestTime());
      $this->groupPermission->setRevisionLogMessage($form_state->getValue('revision_log'));
      $this->groupPermission->save();
      $this->messenger()->addMessage($this->t('The changes have been saved.'));
    }
    else {
      foreach ($violations as $violation) {
        $this->messenger()->addError($violation->getMessage());
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function getGroupRoles() {
    return $this->groupPermissionsManager->getNonAdminRoles($this->group);
  }

}
