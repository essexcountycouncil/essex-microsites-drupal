<?php

namespace Drupal\groupmedia_vbo\Plugin\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\groupmedia\AttachMediaToGroup;
use Drupal\media\MediaInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Assign media to Group.
 *
 * @Action(
 *   id = "vbo_assign_media_to_group",
 *   label = @Translation("VBO: Assign media to a Group"),
 *   type = "media",
 *   pass_context = TRUE
 * )
 */
class AssignMediaToGroup extends ViewsBulkOperationsActionBase implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Attach media tp group service.
   *
   * @var \Drupal\groupmedia\AttachMediaToGroup
   */
  protected $attachMediaToGroup;

  /**
   * AssignMediaToGroup constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\groupmedia\AttachMediaToGroup $attach_media_to_group
   *   Attach media tp group service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AttachMediaToGroup $attach_media_to_group
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->attachMediaToGroup = $attach_media_to_group;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('groupmedia.attach_group')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(MediaInterface $media = NULL) {
    if (empty($media)) {
      return;
    }
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $this->entityTypeManager
      ->getStorage('group')
      ->load($this->configuration['group_id']);
    $this->attachMediaToGroup->assignMediaToGroups([$media], [$group]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'group_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['group_id'] = [
      '#title' => $this->t('Add to Group'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'group',
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['group_id'] = $form_state->getValue('group_id');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\media\MediaInterface $object */
    $result = $object->access('update', $account, TRUE);
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
