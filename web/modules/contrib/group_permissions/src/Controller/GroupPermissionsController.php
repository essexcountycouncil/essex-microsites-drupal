<?php

namespace Drupal\group_permissions\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\group\Access\GroupPermissionHandlerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_permissions\Entity\GroupPermissionInterface;
use Drupal\group_permissions\Entity\Storage\GroupPermissionStorageInterface;
use Drupal\group_permissions\GroupPermissionsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GroupPermissionsController.
 *
 *  Returns responses for group permissions routes.
 */
class GroupPermissionsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $groupPermissionStorage;

  /**
   * The group permissions manager.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManagerInterface
   */
  protected $groupPermissionsManager;

  /**
   * The permission handler.
   *
   * @var \Drupal\group\Access\GroupPermissionHandlerInterface
   */
  protected $groupPermissionHandler;

  /**
   * GroupPermissionsController constructor.
   *
   * @param \Drupal\group_permissions\Controller\DateFormatter $date_formatter
   *   Date formatter.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer.
   * @param \Drupal\group_permissions\Controller\FormBuilderInterface $form_builder
   *   Entity form builder.
   * @param \Drupal\group_permissions\Entity\Storage\GroupPermissionStorageInterface $group_permission_storage
   *   Group permission storage.
   * @param \Drupal\group_permissions\GroupPermissionsManagerInterface $group_permission_manager
   *   The group permissions manager.
   * @param \Drupal\group\Access\GroupPermissionHandlerInterface $permission_handler
   *   The permission handler.
   */
  public function __construct(
    DateFormatter $date_formatter,
    Renderer $renderer,
    FormBuilderInterface $form_builder,
    GroupPermissionStorageInterface $group_permission_storage,
    GroupPermissionsManagerInterface $group_permission_manager,
    GroupPermissionHandlerInterface $permission_handler
  ) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->formBuilder = $form_builder;
    $this->groupPermissionStorage = $group_permission_storage;
    $this->groupPermissionsManager = $group_permission_manager;
    $this->groupPermissionHandler = $permission_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('form_builder'),
      $container->get('entity_type.manager')->getStorage('group_permission'),
      $container->get('group_permissions.group_permissions_manager'),
      $container->get('group.permissions')
    );
  }

  /**
   * Displays a group permissions revision.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   * @param int $group_permission_revision
   *   The group permissions revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow(GroupInterface $group, $group_permission_revision) {

    $rows = [];

    $header = [
      'title' => $this->t('Permission'),
    ];

    $group_permission = $this->groupPermissionsManager->loadByGroup($group);
    $custom_permissions = $group_permission->getPermissions();

    $revision = $this->groupPermissionStorage->loadRevision($group_permission_revision);
    $revision_custom_permissions = $revision->getPermissions();

    $group_roles = $this->groupPermissionsManager->getNonAdminRoles($group);

    // Retrieve information for every role to user further down. We do this to
    // prevent the same methods from being fired (rows * permissions) times.
    foreach ($group_roles as $role_name => $group_role) {
      $permissions = [];

      // Permissions should be explicitly assigned another case we don't
      // provide the permission.
      if (!empty($custom_permissions[$role_name])) {
        $permissions = $custom_permissions[$role_name];
      }

      $role_label = $group_role->label();

      $role_info[$role_name] = [
        'label' => $role_label,
        'permissions' => $permissions,
        'is_anonymous' => $group_role->isAnonymous(),
        'is_outsider' => $group_role->isOutsider(),
        'is_member' => $group_role->isMember(),
      ];
    }

    // Create a column with header for every group role.
    foreach ($role_info as $role_name => $info) {
      $header[$role_name] = [
        'data' => $info['label'],
        'class' => 'checkbox',
      ];
    }

    $permissions_list = $this->getPermissions($group);

    foreach ($permissions_list as $provider => $sections) {
      // Print a full width row containing the provider name for each provider.
      $rows[] = [
        [
          'colspan' => count($group_roles) + 1,
          'class' => 'module',
          'data' => $this->moduleHandler()->getName($provider),
        ],
      ];

      foreach ($sections as $section => $permissions) {
        $rows[] = [
          [
            'colspan' => count($group_roles) + 1,
            'class' => 'section',
            'data' => $section,
          ],
        ];

        foreach ($permissions as $perm => $perm_item) {

          $row = [];
          $row[] = [
            'data' => [
              '#type' => 'inline_template',
              '#template' => '<span class="title">{{ title }}</span>{% if description or warning %}<div class="description">{% if warning %}<em class="permission-warning">{{ warning }}</em><br />{% endif %}{{ description }}</div>{% endif %}',
              '#context' => [
                'title' => $perm_item['title'],
                'description' => $perm_item['description'],
                'warning' => $perm_item['warning'],
              ],
            ],
            'class' => 'permission',
          ];

          foreach ($role_info as $role_name => $info) {
            // Determine whether the permission is available for this role.
            $na = $info['is_anonymous'] && !in_array('anonymous', $perm_item['allowed for']);
            $na = $na || ($info['is_outsider'] && !in_array('outsider', $perm_item['allowed for']));
            $na = $na || ($info['is_member'] && !in_array('member', $perm_item['allowed for']));

            if ($na) {
              $row[] = [
                'data' => [
                  '#type' => 'markup',
                  '#markup' => '-',
                ],
                'class' => 'checkbox module',
              ];
            }
            else {

              $group_permissions_has_permission = !empty($custom_permissions[$role_name]) && in_array($perm, $custom_permissions[$role_name]);
              $revision_has_permission = !empty($revision_custom_permissions[$role_name]) && in_array($perm, $revision_custom_permissions[$role_name]);
              $sign = $group_permissions_has_permission ? '&check;' : '&times;';

              if (!$group_permissions_has_permission) {
                $color = '#000000';
              }
              else {
                $color = $group_permissions_has_permission == $revision_has_permission ? '#008000' : '#ff0000';
              }
              $row[] = [
                'data' => [
                  '#type' => 'markup',
                  '#markup' => $sign,
                ],
                'style' => "color: {$color}; font-size: 30px;",
                'class' => 'checkbox module',
              ];
            }

          }

          $rows[] = $row;
        }
      }
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No content has been found.'),
      '#attributes' => ['class' => ['permissions', 'js-permissions']],
    ];

    $build['table']['#attached']['library'][] = 'group/permissions';

    return [
      '#type' => '#markup',
      '#markup' => $this->renderer->render($build),
    ];
  }

  /**
   * Gets the permissions to display.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   A multidimensional associative array of permissions, keyed by the
   *   providing module first and then by permission name.
   */
  protected function getPermissions(GroupInterface $group) {
    $by_provider_and_section = [];

    // Create a list of group permissions ordered by their provider and section.
    foreach ($this->groupPermissionHandler->getPermissionsByGroupType($group->getGroupType()) as $permission_name => $permission) {
      $by_provider_and_section[$permission['provider']][$permission['section_id']][$permission_name] = $permission;
    }

    // Always put the 'general' section at the top if provided.
    foreach ($by_provider_and_section as $provider => $sections) {
      if (isset($sections['general'])) {
        $by_provider_and_section[$provider] = ['general' => $sections['general']] + $sections;
      }
    }

    return $by_provider_and_section;
  }

  /**
   * Page title callback for a group permissions revision.
   *
   * @param int $group_permission_revision
   *   The group permissions revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($group_permission_revision) {
    $group_permission = $this->groupPermissionStorage
      ->loadRevision($group_permission_revision);
    return $this->t('%title (Rev. %revision)', [
      '%title' => $group_permission->label(),
      '%revision' => $group_permission_revision,
    ]);
  }

  /**
   * Generates an overview table of older revisions.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function revisionOverview(GroupInterface $group) {
    $group_permission = $this->groupPermissionsManager->loadByGroup($group);
    $build['#title'] = $this->t('Revisions for %title', ['%title' => $group_permission->getGroup()->label()]);
    $header = [
      $this->t('Revision'),
      $this->t('Operations'),
    ];

    $rows = [];
    $default_revision = $group_permission->getRevisionId();
    $current_revision_displayed = FALSE;

    foreach ($this->getRevisionIds($group_permission) as $vid) {
      $revision_url_parameters = [
        'group_permission_revision' => $vid,
        'group_permission' => $group_permission->id(),
        'group' => $group_permission->getGroup()->id(),
      ];
      /** @var \Drupal\group_permissions\Entity\GroupPermissionInterface $revision */
      $revision = $this->groupPermissionStorage->loadRevision($vid);
      $username = [
        '#theme' => 'username',
        '#account' => $revision->getRevisionUser(),
      ];

      // Use revision link to link to revisions that are not active.
      $date = $this->dateFormatter->format($revision->revision_created->value, 'short');

      // We treat also the latest translation-affecting revision as current
      // revision, if it was the default revision, as its values for the
      // current language will be the same of the current default revision in
      // this case.
      $is_current_revision = $vid == $default_revision || (!$current_revision_displayed && $revision->wasDefaultRevision());
      if (!$is_current_revision) {
        $link = Link::fromTextAndUrl($date, new Url('entity.group_permission.revision', $revision_url_parameters))->toString();
      }
      else {
        $link = $group_permission->toLink($date)->toString();
        $current_revision_displayed = TRUE;
      }

      $row = [];
      $column = [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
          '#context' => [
            'date' => $link,
            'username' => $this->renderer->renderPlain($username),
            'message' => [
              '#markup' => $revision->getRevisionLogMessage(),
              '#allowed_tags' => Xss::getHtmlTagList(),
            ],
          ],
        ],
      ];
      // @todo Simplify once https://www.drupal.org/node/2334319 lands.
      $this->renderer->addCacheableDependency($column['data'], $username);
      $row[] = $column;
      $links = [];
      if ($is_current_revision) {
        $row[] = [
          'data' => [
            '#prefix' => '<em>',
            '#markup' => $this->t('Current revision'),
            '#suffix' => '</em>',
          ],
        ];

        $rows[] = [
          'data' => $row,
          'class' => ['revision-current'],
        ];
      }
      else {

        $links['revert'] = $this->getRevertLink($revision_url_parameters);
        $links['delete'] = $this->getDeleteLink($revision_url_parameters);

        $row[] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $links,
          ],
        ];

        $rows[] = $row;
      }

    }

    $build['group_permission_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Returns an array with a revert revision link.
   *
   * @param array $url_parameters
   *   Url params.
   *
   * @return array
   *   Link array.
   */
  private function getRevertLink(array $url_parameters) {
    return $this->getLinkArray($url_parameters, $this->t('Revert'), 'revision-revert');
  }

  /**
   * Returns an array with a delete revision link.
   *
   * @param array $url_parameters
   *   Url params.
   *
   * @return array
   *   Link array.
   */
  private function getDeleteLink(array $url_parameters) {
    return $this->getLinkArray($url_parameters, $this->t('Delete'), 'revision-delete');
  }

  /**
   * Get link array.
   *
   * @param array $url_parameters
   *   Url params.
   * @param string $title
   *   Title.
   * @param string $action
   *   Action.
   *
   * @return array
   *   Link array.
   */
  private function getLinkArray(array $url_parameters, $title, $action): array {
    return [
      'title' => $this->t(':title', [':title' => $title])->render(),
      'url' => Url::fromRoute("entity.group_permission.$action", $url_parameters),
    ];
  }

  /**
   * Gets a list of revisions IDs for a specific group permission.
   *
   * @param \Drupal\group_permissions\Entity\GroupPermissionInterface $group_permission
   *   The group permission entity.
   *
   * @return int[]
   *   Group permission revision IDs (in descending order).
   */
  protected function getRevisionIds(GroupPermissionInterface $group_permission) {
    $result = $this->groupPermissionStorage->getQuery()
      ->allRevisions()
      ->condition($group_permission->getEntityType()->getKey('id'), $group_permission->id())
      ->sort($group_permission->getEntityType()->getKey('revision'), 'DESC')
      ->pager(50)
      ->execute();
    return array_keys($result);
  }

}
