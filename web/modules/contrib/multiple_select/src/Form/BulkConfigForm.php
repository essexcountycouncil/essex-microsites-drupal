<?php

namespace Drupal\multiple_select\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * BulkConfigForm config form.
 */
class BulkConfigForm extends ConfigFormBase {


  /**
   * Provides an interface for an entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Provides an interface for entity type managers.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;


  /**
   * Undocumented variable.
   *
   * @var array
   */
  protected static $options = [
    'node' => [
      'readable' => 'Node',
      'type' => 'node_type',
    ],
    'media' => [
      'readable' => 'Media',
      'type' => 'media_type',
    ],
    'site_setting_entity' => [
      'readable' => 'Site Settings',
      'type' => 'site_setting_entity_type',
    ],
    'taxonomy_term' => [
      'readable' => 'Taxonomy',
      'type' => 'taxonomy_term',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($config_factory);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multiple_select_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['multiple_select.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $data = $this->config('multiple_select.settings')->get('table');

    if (!is_null($data)) {
      $shared_bulk_config = json_decode($data, TRUE);
    }

    foreach (self::$options as $type => $entity) {
      $entity_types = $this->getTypes($entity['type']);

      if (count($entity_types) > 0) {

        $form[$type] = [
          '#type' => 'label',
          '#title' => $entity['readable'],
          '#prefix' => '<br/>',
        ];
      }

      foreach ($entity_types as $entity_type) {
        $entity_fields = [];
        $bundle_fields = $this->entityFieldManager->getFieldDefinitions($type, $entity_type->id());
        foreach ($bundle_fields as $field) {
          if ($field->getType() == 'list_string' || $field->getType() == 'entity_reference') {
            $entity_fields[$field->getName()] = $field->getLabel();
          }
        }

        $default = !is_null($data) && isset($shared_bulk_config[$entity_type->id()]) ? 1 : 0;
        $form[$entity_type->id()] = [
          '#type' => 'checkbox',
          '#title' => $entity_type->label(),
          '#default_value' => $default,
        ];

        if (empty($entity_fields)) {
          $form[$entity_type->id()]['#disabled'] = TRUE;
        }
        else {
          $default_fields = !is_null($data) && isset($shared_bulk_config[$entity_type->id()]) ? $shared_bulk_config[$entity_type->id()] : NULL;
          $form[$entity_type->id() . '_' . $type] = [
            '#type' => 'select',
            '#multiple' => TRUE,
            '#default_value' => $default_fields,
            '#options' => $entity_fields,
            '#states' => [
              'visible' => [
                ':input[name="' . $entity_type->id() . '"]' => ['checked' => TRUE],
              ],
            ],
          ];
        }

      }
    }
    $form['text'] = [
      '#markup' => $this->t('NOTE: only field of type "Check boxes" will be affected.') . '<br>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('multiple_select.settings')->set('table', NULL)->save();
    foreach (self::$options as $type => $entity) {
      foreach ($this->getTypes($entity['type']) as $entity_type) {
        if ($form_state->getValue($entity_type->id()) != 0) {
          $shared_bulk_config[$entity_type->id()] = $form_state->getValue($entity_type->id() . '_' . $type);
        }
      }
    }
    if (isset($shared_bulk_config)) {
      $this->configFactory->getEditable('multiple_select.settings')->set('table', json_encode($shared_bulk_config))->save();
    }
    $this->messenger()->addStatus($this->t('Configurations successfully saved.'));
  }

  /**
   * Get existing entity types.
   */
  public function getTypes($type) {
    $types = [];
    if ($this->entityTypeManager->hasDefinition($type)) {
      if ($type == 'taxonomy_term') {
        $vocs = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
        foreach ($vocs as $voc) {
          $types[] = $voc;
        }
      }
      else {
        foreach ($this->entityTypeManager->getStorage($type)->loadMultiple() as $instance) {
          $types[] = $instance;
        }
      }
    }
    return $types;
  }

}
