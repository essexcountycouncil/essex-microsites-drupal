<?php

namespace Drupal\groupmedia\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GroupMediaSettingsForm.
 *
 * @package Drupal\groupmedia\Form
 */
class GroupMediaSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['groupmedia.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'groupmedia_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('groupmedia.settings');

    $media_bundles = $this->entityTypeManager->getStorage('media_type')->loadMultiple();

    $options = [];
    foreach ($media_bundles as $media_bundle) {
      $options[$media_bundle->id()] = $media_bundle->label();
    }
    asort($options);

    $form['tracking_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable media tracking'),
      '#description' => $this->t('If enabled the system would try to attach media items referenced in group content to corresponding group'),
      '#default_value' => $config->get('tracking_enabled'),
    ];

    $form['bundles'] = [
      '#title' => $this->t('Media types to exclude'),
      '#description' => $this->t('By default all media bundles will be tracked. Here you can exclude certain media bundles and prevent them from being automatically attached to the group/s'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $config->get('bundles'),
      '#states' => [
        'visible' => [
          ':input[name="tracking_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('groupmedia.settings')
      ->set('tracking_enabled', $form_state->getValue('tracking_enabled'))
      ->set('bundles', $form_state->getValue('bundles'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
