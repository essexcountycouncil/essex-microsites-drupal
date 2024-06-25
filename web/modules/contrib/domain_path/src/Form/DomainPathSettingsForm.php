<?php

namespace Drupal\domain_path\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Class DomainPathSettingsForm.
 *
 * @package Drupal\domain_path\Form
 * @ingroup domain_path
 */
class DomainPathSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
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
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'domain_path_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['domain_path.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('domain_path.settings');
    $enabled_entity_types = $config->get('entity_types');

    $form['entity_types'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Enabled entity types for domain paths'),
      '#tree' => TRUE,
    ];

    // Get all applicable entity types.
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (is_subclass_of($entity_type->getClass(), FieldableEntityInterface::class) && $entity_type->hasLinkTemplate('canonical')) {
        $default_value = !empty($enabled_entity_types[$entity_type_id]) ? $enabled_entity_types[$entity_type_id] : NULL;
        if ($entity_type_id == 'domain_path' || $entity_type_id == 'domain_path_redirect') {
          continue;
        }
        $form['entity_types'][$entity_type_id] = [
          '#type' => 'checkbox',
          '#title' => $entity_type->getLabel(),
          '#default_value' => $default_value,
        ];
      }
    }
/*
    $form['ui'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('UI Settings'),
    ];
*/

    $form['language_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('The method of language detection'),
      '#default_value' => !empty($config->get('alias_title')) ? $config->get('alias_title') : 'name',
      '#options' => [
        LanguageInterface::TYPE_CONTENT => $this->t('Content language'),
        LanguageInterface::TYPE_INTERFACE => $this->t('Interface text language'),
        LanguageInterface::TYPE_URL => $this->t('Language from URLs')
      ],
      '#default_value' => !empty($config->get('language_method')) ? $config->get('language_method') : LanguageInterface::TYPE_CONTENT,
      '#description' => $this->t('If you enabled multilingual content for certain domains, you need to set it according to your language settings.'),
    ];
    $options = [
      'name' => $this->t('The domain display name'),
      'hostname' => $this->t('The raw hostname'),
      'url' => $this->t('The domain base URL'),
    ];

    $form['alias_title'] = [
      '#type' => 'radios',
      '#title' => $this->t('Domain path alias title'),
      '#default_value' => !empty($config->get('alias_title')) ? $config->get('alias_title') : 'name',
      '#options' => $options,
      '#description' => $this->t('Select the text to display for each field in entity edition.'),
    ];

    $form['hide_path_alias_ui'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide the default URL alias UI'),
      '#default_value' => !empty($config->get('hide_path_alias_ui')) ? $config->get('hide_path_alias_ui') : FALSE,
      '#description' => $this->t('Hide the default URL alias options from the UI to avoid the confusion. Domain path will replace the default URL alias with each individual domains alias'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('domain_path.settings')
      ->set('entity_types', $form_state->getValue('entity_types'))
      ->set('language_method', $form_state->getValue('language_method'))
      ->set('alias_title', $form_state->getValue('alias_title'))
      ->set('hide_path_alias_ui', $form_state->getValue('hide_path_alias_ui'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
