<?php

namespace Drupal\group_content_menu\Controller;

use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\group\Entity\Controller\GroupRelationshipController;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_content_menu\Form\MenuLinkItemDeleteForm;
use Drupal\group_content_menu\Form\MenuLinkItemForm;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for 'group_content_menu' GroupRelationship routes.
 */
class GroupContentMenuController extends GroupRelationshipController {

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManager
   */
  protected $pluginManager;

  /**
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface;
   */
  protected $classResolver;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->privateTempStoreFactory = $container->get('tempstore.private');
    $instance->pluginManager = $container->get('group_relation_type.manager');
    $instance->classResolver = $container->get('class_resolver');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function addPage(GroupInterface $group, $create_mode = TRUE, $base_plugin_id = 'group_content_menu') {
    $relationship_types = $this->addPageBundles($group, $create_mode, $base_plugin_id);
    // Filter out the bundles the user doesn't have access to. Duplicated from
    // parent class so as to avoid information disclosure.
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('group_relationship');
    foreach ($relationship_types as $bundle_id => $relationship_type) {
      $access = $access_control_handler->createAccess($bundle_id, NULL, ['group' => $group], TRUE);
      if (!$access->isAllowed()) {
        unset($relationship_types[$bundle_id]);
      }
    }

    // Disallow creating multiple menus of the same type.
    if (count($relationship_types) === 1) {
      $relationship_type = reset($relationship_types);
      if ($limitation = $this->handleOneMenuLimitation($group, $relationship_type->getPlugin()->getDerivativeId())) {
        return $limitation;
      }
    }

    return parent::addPage($group, $create_mode, $base_plugin_id);
  }

  /**
   * Handle one menu per group limitation.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   * @param string $plugin_id
   *   The group content plugin ID.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|bool
   *   The redirect response or FALSE if no need to handle..
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function handleOneMenuLimitation(GroupInterface $group, $plugin_id) {
    if ($group_relationships = $this->entityTypeManager->getStorage('group_relationship')->loadByGroup($group, $plugin_id)) {
      $group_relationship = reset($group_relationships);
      if ($menu_type = $this->entityTypeManager->getStorage('group_content_menu_type')->load($group_relationship->getEntity()->bundle())) {
        $this->messenger()->addError($this->t('This group already has a menu "%menu" of type "%type". Only one menu per type per group is allowed.', [
          '%menu' => $group_relationship->getEntity()->label(),
          '%type' => $menu_type->label(),
        ]));
        $route_params = ['group' => $group_relationship->getGroup()->id()];
        $url = Url::fromRoute('entity.group_content_menu.collection', $route_params, ['absolute' => TRUE]);
        return new RedirectResponse($url->toString());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createForm(GroupInterface $group, $plugin_id) {
    if ($limitation = $this->handleOneMenuLimitation($group, $plugin_id)) {
      return $limitation;
    }

    return parent::createForm($group, $plugin_id);
  }

  /**
   * Provides the menu link creation form.
   *
   * @param \Drupal\group_content_menu\GroupContentMenuInterface $group_content_menu
   *   An entity representing a custom menu.
   *
   * @return array
   *   Returns the menu link creation form.
   */
  public function addLink(GroupContentMenuInterface $group_content_menu) {
    $menu_name = GroupContentMenuInterface::MENU_PREFIX . $group_content_menu->id();
    $menu_link = $this->entityTypeManager()->getStorage('menu_link_content')->create([
      'menu_name' => $menu_name,
      'bundle' => 'menu_link_content',
    ]);
    return $this->entityFormBuilder()->getForm($menu_link);
  }

  /**
   * Provides the menu link edit form.
   *
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $menu_link_content
   *   The menu link content.
   *
   * @return array
   *   Returns the menu link edit form.
   */
  public function editLink(MenuLinkContentInterface $menu_link_content) {
    return $this->getForm(MenuLinkItemForm::class, $menu_link_content);
  }

  /**
   * Provides the menu link delete form.
   *
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $menu_link_content
   *   The menu link content.
   *
   * @return array
   *   Returns the menu link delete form.
   */
  public function deleteLink(MenuLinkContentInterface $menu_link_content) {
    return $this->getForm(MenuLinkItemDeleteForm::class, $menu_link_content);
  }

  private function getForm(string $class, MenuLinkContentInterface $entity) {
    $form_object = $this->classResolver->getInstanceFromDefinition($class);
    $form_object->setEntity($entity);
    $form_object
      ->setStringTranslation($this->getStringTranslation())
      ->setModuleHandler($this->moduleHandler())
      ->setEntityTypeManager($this->entityTypeManager);

    $form_state = new FormState();
    return $this->formBuilder()->buildForm($form_object, $form_state);
  }

}
