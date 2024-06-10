<?php

namespace Drupal\localgov_microsites_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\localgov_microsites_group\Form\DomainGroupConfigAdd;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Group routes.
 */
class DomainGroupAddController extends ControllerBase {

  /**
   * The private store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

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
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new GroupController.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The private store factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, RendererInterface $renderer) {
    $this->privateTempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('renderer')
    );
  }

  /**
   * Provides the group creation form.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The type of group to create.
   *
   * @return array
   *   A group submission form.
   */
  public function addForm(GroupTypeInterface $group_type) {
    $wizard_id = 'domain_group_add';
    $store = $this->privateTempStoreFactory->get($wizard_id);
    $store_id = $group_type->id();
    $extra['group_wizard'] = 'group_wizard';
    $extra['group_wizard_id'] = $wizard_id;
    // Pass the group type and store ID to the form state as well.
    $extra['group_type'] = $group_type;
    $extra['store_id'] = $store_id;

    // See if we are on the second step of the form.
    $step = $store->get("$store_id:step");
    // Group form, potentially as wizard step 1.
    if (empty($step) || $step == 1) {
      $storage = $this->entityTypeManager()->getStorage('group');

      // Only create a new group if we have nothing stored.
      if (!$entity = $store->get("$store_id:entity")) {
        $values['type'] = $group_type->id();
        $entity = $storage->create($values);
      }

      $form = $this->entityFormBuilder()->getForm($entity, 'new_domain', $extra);
      // Changes to the form here are visual, as when it is submitted it never
      // gets to this point before running the handlers.
      $form['revision_information']['#access'] = FALSE;
      $form['uid']['#required'] = TRUE;
      $form['uid']['widget'][0]['target_id']['#title'] = $this->t('Owner');
      $form['uid']['widget'][0]['target_id']['#description'] = $this->t('Microsite owner and first administrator');
    }
    // Wizard step 2: Group membership form.
    elseif ($step == 2 && $group_type->creatorMustCompleteMembership()) {
      // Only create a new group if we have nothing stored.
      if (!$entity = $store->get("$store_id:membership")) {
        // Create an empty group membership that does not yet have a group set.
        $values = [
          'type' => $group_type->getContentPlugin('group_membership')->getContentTypeConfigId(),
          'entity_id' => $this->currentUser()->id(),
        ];
        $entity = $this->entityTypeManager()->getStorage('group_relationship')->create($values);
      }
      $form = $this->entityFormBuilder()->getForm($entity, 'new_domain', $extra);
    }
    else {
      $group = $store->get("$store_id:entity");
      $form = $this->formBuilder()->getForm(DomainGroupConfigAdd::class, $group, $extra);
    }

    return $form;
  }

  /**
   * The _title_callback for the group.add route.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The type of group to create.
   *
   * @return string
   *   The page title.
   */
  public function addFormTitle(GroupTypeInterface $group_type) {
    return $this->t('Add @group_type_label', [
      '@group_type_label' => $group_type->label(),
    ]);
  }

}
