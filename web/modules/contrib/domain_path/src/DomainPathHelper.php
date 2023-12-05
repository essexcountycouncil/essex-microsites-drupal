<?php

namespace Drupal\domain_path;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\domain\DomainInterface;

class DomainPathHelper {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $accountManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * DomainPathHelper constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account_manager
   *   The account manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(AccountInterface $account_manager, EntityTypeManagerInterface $entity_type_manager, AliasManagerInterface $alias_manager, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->accountManager = $account_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasManager = $alias_manager;
    $this->moduleHandler = $module_handler;
    $this->config = $config_factory->get('domain_path.settings');
  }

  /**
   * The domain paths form element for the entity form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Referenced entity.
   *
   * @return array $form
   *   Return the modified form array.
   */
  public function alterEntityForm(&$form, FormStateInterface $form_state, $entity) {
    $domains = $this->entityTypeManager->getStorage('domain')->loadMultipleSorted();
    $config = \Drupal::config('domain_path.settings');
    // Just exit if domain paths is not enabled for this entity.
    if (!$this->domainPathsIsEnabled($entity) || !$domains) {
      return $form;
    }

    // Set up our variables.
    $entity_id = $entity->id();
    $langcode = $entity->language()->getId();
    $show_delete = FALSE;
    $default = '';

    // Container for domain path fields
    $form['path']['widget'][0]['domain_path'] = [
      '#tree' => TRUE,
      '#type' => 'details',
      '#title' => $this->t('Domain-specific paths'),
      '#description' => $this->t('Override the default URL alias (above) for individual domains.'),
      // '#group' => 'path_settings',
      '#weight' => 110,
      '#open' => TRUE,
      '#access' => $this->accountManager->hasPermission('edit domain path entity'),
    ];

    // Add an option to delete all domain paths. This is just for convenience
    // so the user doesn't have to manually remove the paths from each domain.
    $form['path']['widget'][0]['domain_path']['domain_path_delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete domain-specific aliases'),
      '#default_value' => FALSE,
    ];

    // Add a domain path field for each domain.
    foreach ($domains as $domain_id => $domain) {
      $form['path']['widget'][0]['domain_path'][$domain_id] = [
        '#type' => 'container'
      ];

      // Gather the existing domain path.
      $path = FALSE;
      if ($entity_id) {
        $properties = [
          'source' => '/' . $entity->toUrl()->getInternalPath(),
          'language' => $langcode,
          'domain_id' => $domain_id,
        ];
        if ($domain_paths = $this->entityTypeManager->getStorage('domain_path')->loadByProperties($properties)) {
          $path = reset($domain_paths)->get('alias')->getString();
        }
      }

      // We only need to enable the delete checkbox if we have at least one
      // domain path.
      if (!$show_delete && $path) {
        $show_delete = TRUE;
      }

      $label = $domain->label();
      if ($config->get('alias_title') == 'hostname') {
        $label = $domain->getHostname();
      }
      elseif ($config->get('alias_title') == 'url') {
        $label = $domain->getPath();
      }

      $form_state->addBuildInfo('pathauto_checkbox', $form['path']['widget'][0]['pathauto']['#type'] === 'checkbox');
      $form['path']['widget'][0]['domain_path'][$domain_id]['path'] = [
        '#type' => 'textfield',
        '#title' => Html::escape(rtrim($label, '/')),
        '#default_value' => $path ? $path : $default,
        '#access' => $this->accountManager->hasPermission('edit domain path entity'),
        '#states' => [
          'disabled' => [
            'input[name="path[0][domain_path][domain_path_delete]"]' => ['checked' => TRUE],
          ]
        ],
      ];

      // If domain settings are on the page for this domain we only show if
      // it's checked. e.g. on the node form, we only show the domain path
      // field for domains we're publishing to
      if (!empty($form['field_domain_access']['widget']['#options'][$domain_id])) {
        if ($form['field_domain_access']['widget']['#type'] == 'select') {
          $form['path']['widget'][0]['domain_path'][$domain_id]['#states']['invisible']['select[name="field_domain_access"]'] = ['!value' => $domain_id];
        }
        elseif ($form['field_domain_access']['widget']['#type'] == 'radios') {
          $form['path']['widget'][0]['domain_path'][$domain_id]['#states']['invisible']['input[name="field_domain_access"]'] = ['!value' => $domain_id];
        }
        else {
          $form['path']['widget'][0]['domain_path'][$domain_id]['#states']['invisible']['input[name="field_domain_access[' . $domain_id . ']"]'] = ['unchecked' => TRUE];
        }
        $form['path']['widget'][0]['domain_path'][$domain_id]['#states']['invisible']['input[name="field_domain_all_affiliates[value]"]'] = ['unchecked' => TRUE];

      }
      else if (!empty($form['field_domain_access']['widget']['#options'])) {
        $form['path']['widget'][0]['domain_path'][$domain_id]['#access'] = FALSE;
      }
    }

    $form['path']['widget'][0]['domain_path']['domain_path_delete']['#access'] = $show_delete;

    // Add our validation and submit handlers.
    $form['#validate'][] = [$this, 'validateEntityForm'];
    if (!empty($form['actions'])) {
      if (array_key_exists('submit', $form['actions'])) {
        $form['actions']['submit']['#submit'][] = [$this, 'submitEntityForm'];
      }
    }
    else {
      // If no actions we just tack it on to the form submit handlers.
      $form['#submit'][] = [$this, 'submitEntityForm'];
    }

    // Hide the default URL alias for better UI
    if ($config->get('hide_path_alias_ui')) {
      $form['domain_path'] = $form['path']['widget'][0]['domain_path'];
      if (isset($form['advanced'])) {
        $form['domain_path']['#group'] = 'advanced';
      }
      unset($form['path']);
    }
  }

  /**
   * Validation handler the domain paths element on the entity form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public static function validateEntityForm(array &$form, FormStateInterface $form_state) {
    // Set up variables.
    $config = \Drupal::config('domain_path.settings');
    $entity = $form_state->getFormObject()->getEntity();
    $domain_path_storage = \Drupal::service('entity_type.manager')->getStorage('domain_path');
    $path_values = $form_state->getValue('path');
    $domain_path_values = ($config->get('hide_path_alias_ui')) ? $form_state->getValue('domain_path') : $path_values[0]['domain_path'];

    // If we're just deleting the domain paths we don't have to validate
    // anything.
    if (!empty($domain_path_values['domain_path_delete'])) {
      return;
    }
    unset($domain_path_values['domain_path_delete']);

    // Check domain access settings if they are on the form.
    $domain_access = [];
    if (!empty($form['field_domain_access']) && !empty($form_state->getValue('field_domain_access'))) {
      foreach ($form_state->getValue('field_domain_access') as $item) {
        if (!is_array($item)) {
          continue;
        }
        $domain_access[$item['target_id']] = $item['target_id'];
      }
    }
    // If domain access is on for this form, we check the "all affiliates"
    // checkbox, otherwise we just assume it's available on all domains.
    $domain_access_all = !empty($form['field_domain_all_affiliates'])
      ? $form_state->getValue('field_domain_all_affiliates')['value'] : TRUE;

    // Validate each path value.
    foreach ($domain_path_values as $domain_id => $domain_path_data) {
      // Don't validate if the domain doesn't have access (we remove aliases
      // for domains that don't have access to this entity).
      $domain_has_access = $domain_access_all || ($domain_access && !empty($domain_access[$domain_id]));
      if (!$domain_has_access) {
        continue;
      }
      $form_state->setValue('domain_path', $domain_path_values);
    }
  }

  /**
   * Submit handler for the domain paths element on the entity form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitEntityForm($form, FormStateInterface $form_state) {
    // Setup Variables
    $config = \Drupal::config('domain_path.settings');
    $entity = $form_state->getFormObject()->getEntity();
    $entity_system_path = '/' . $entity->toUrl()->getInternalPath();
    // Get the saved alias
    $default_alias = $this->aliasManager->getAliasByPath($entity_system_path);
    $properties = [
      'source' => $entity_system_path,
      'language' => $entity->language()->getId(),
    ];
    $path_values = $form_state->getValue('path');
    $domain_path_values = ($config->get('hide_path_alias_ui')) ? $form_state->getValue('domain_path') : $path_values[0]['domain_path'];
    $domain_path_storage = $this->entityTypeManager->getStorage('domain_path');

    // Check domain access settings if they are on the form.
    $domain_access = [];
    if (!empty($form['field_domain_access']) && !empty($form_state->getValue('field_domain_access'))) {
      $domain_access = $this->processDomainAccessField($form_state->getValue('field_domain_access'));
    }
    // If domain access is on for this form, we check the "all affiliates"
    // checkbox, otherwise we just assume it's available on all domains.
    $domain_access_all = !empty($form['field_domain_all_affiliates']) ? $form_state->getValue('field_domain_all_affiliates')['value'] : TRUE;

    // If not set to delete, then save changes.
    if (empty($domain_path_values['domain_path_delete'])) {
      unset($domain_path_values['domain_path_delete']);
      foreach ($domain_path_values as $domain_id => $domain_path_data) {

        $alias = trim($domain_path_data['path']);
        // Get the existing domain path for this domain if it exists.
        $properties['domain_id'] = $domain_id;
        $domain_paths = $domain_path_storage->loadByProperties($properties);
        $domain_path = $domain_paths ? reset($domain_paths) : NULL;
        $domain_has_access = $domain_access_all || ($domain_access && !empty($domain_access[$domain_id]));

        // We don't want to save the alias if the domain path field is empty,
        // or if the domain doesn't have
        // access to this entity.
        if (!$alias || !$domain_has_access) {
          // Delete the existing domain path.
          if ($domain_path) {
            $domain_path->delete();
          }
          continue;
        }

        // Create or update the domain path.
        $properties_map = [
            'alias' => $alias,
            'domain_id' => $domain_id,
          ] + $properties;
        if (!$domain_path) {
          $domain_path = $domain_path_storage->create(['type' => 'domain_path']);
          foreach ($properties_map as $field => $value) {
            $domain_path->set($field, $value);
          }
          $domain_path->save();
        }
        else {
          if ($domain_path->get('alias')->value != $alias) {
            $domain_path->set('alias', $alias);
            $domain_path->save();
          }
        }
      }
    }
    else {
      // Delete all domain path aliases.
      $domain_paths = $domain_path_storage->loadByProperties($properties);
      foreach ($domain_paths as $domain_path) {
        $domain_path->delete();
      }
    }
  }

  /**
   * Helper function for deleting domain paths from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function deleteEntityDomainPaths(EntityInterface $entity, $delete_all_translations = FALSE) {
    if ($this->domainPathsIsEnabled($entity)) {
      $properties_map = [
        'source' => '/' . $entity->toUrl()->getInternalPath(),
      ];
      if (!$delete_all_translations) {
        $properties_map['language'] = $entity->language()->getId();
      }
      $domain_paths = $this->entityTypeManager
        ->getStorage('domain_path')
        ->loadByProperties($properties_map);
      if ($domain_paths) {
        foreach ($domain_paths as $domain_path) {
          $domain_path->delete();
        }
      }
    }

    // Delete domain paths on domain delete.
    if ($entity instanceof DomainInterface) {
      $domain_paths = $this->entityTypeManager
        ->getStorage('domain_path')
        ->loadByProperties(['domain_id' => $entity->id()]);
      if ($domain_paths) {
        foreach ($domain_paths as $domain_path) {
          $domain_path->delete();
        }
      }
    }
  }

  /**
   * Helper function for retrieving configured entity types.
   *
   * @return array
   *   Returns array of configured entity types.
   */
  public function getConfiguredEntityTypes() {
    $enabled_entity_types = $this->config->get('entity_types') ?? [];
    $enabled_entity_types = array_filter($enabled_entity_types);

    return array_keys($enabled_entity_types);
  }

  /**
   * Check if domain paths is enabled for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return boolean
   *   Return TRUE or FALSE.
   */
  public function domainPathsIsEnabled(EntityInterface $entity) {
    return in_array($entity->getEntityTypeId(), $this->getConfiguredEntityTypes());
  }

  /**
   * Returns an array of processed domain access field values.
   */
  public function processDomainAccessField($field_values): array {
    $domain_access = [];
    foreach ($field_values as $field_value) {
      if (!is_array($field_value)) {
        continue;
      }
      $domain_access[$field_value['target_id']] = $field_value['target_id'];
    }

    return $domain_access;
  }

}
