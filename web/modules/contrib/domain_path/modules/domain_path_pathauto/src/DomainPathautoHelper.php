<?php

namespace Drupal\domain_path_pathauto;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\domain_path\DomainPathHelper;

/**
 * DomainPathauto helper service.
 */
class DomainPathautoHelper {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * DomainPathautoGenerator service.
   *
   * @var \Drupal\domain_path_pathauto\DomainPathautoGenerator
   */
  protected DomainPathautoGenerator $domainPathautoGenerator;

  /**
   * ConfigFactory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * DomainPath helper service.
   *
   * @var \Drupal\domain_path\DomainPathHelper
   */
  protected DomainPathHelper $domainPathHelper;

  /**
   * DomainPathautoHelper constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    DomainPathautoGenerator $domain_pathauto_generator,
    ConfigFactoryInterface $config_factory,
    DomainPathHelper $domain_path_helper
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->domainPathautoGenerator = $domain_pathauto_generator;
    $this->configFactory = $config_factory;
    $this->domainPathHelper = $domain_path_helper;
  }

  /**
   * The domain path_auto form element for the entity form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Related entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function alterEntityForm(array &$form, FormStateInterface $form_state, ContentEntityInterface $entity, array $keys) {
    // We need to use it in validation callback.
    $form_state->addBuildInfo('element_keys', $keys);
    $domains = $this->entityTypeManager->getStorage('domain')
      ->loadMultipleSorted();
    // When setting states of checkboxes it's matter whether we have enabled or
    // disabled default pathauto UI. Depending on it, we have different paths.
    $input_selector_prefix = 'input[name="domain_path';
    if (!$this->configFactory->get('domain_path.settings')->get('hide_path_alias_ui')) {
      $input_selector_prefix = 'input[name="path[0][domain_path]';
    }
    $build_info = $form_state->getBuildInfo();
    foreach ($domains as $domain_id => $domain) {
      // See https://git.drupalcode.org/project/pathauto/-/blob/8.x-1.x/src/PathautoWidget.php#L42
      // Generate checkboxes per each domain.
      if ($build_info['pathauto_checkbox'] && NestedArray::getValue($form, $keys)) {
        NestedArray::setValue($form, array_merge($keys, [$domain_id, 'pathauto']), [
          '#type' => 'checkbox',
          '#title' => $this->t('Generate automatic URL alias for @domain', ['@domain' => Html::escape(rtrim($domain->getPath(), '/'))]),
          '#default_value' => $this->domainPathautoGenerator->domainPathPathautoGenerationIsEnabled($entity, $domain->id()),
          '#weight' => -1,
        ]);
      }
      // Disable form element if the "delete-checkbox" is active or automatic
      // creation of alias is checked.
      NestedArray::setValue($form, array_merge($keys, [$domain_id, 'path', '#states']), [
        'disabled' => [
          [$input_selector_prefix . '[domain_path_delete]"]' => ['checked' => TRUE]],
          'OR',
          [$input_selector_prefix . '[' . $domain_id . '][pathauto]"]' => ['checked' => TRUE]],
        ],
      ]);
    }
    $form['#validate'][] = [self::class, 'validateAlteredForm'];
    if (!empty($form['actions'])) {
      if (array_key_exists('submit', $form['actions'])) {
        $form['actions']['submit']['#submit'][] = [
          self::class,
          'submitAlteredEntityForm',
        ];
      }
    }
    else {
      // If no actions we just tack it on to the form submit handlers.
      $form['#submit'][] = [self::class, 'submitAlteredEntityForm'];
    }
  }

  /**
   * Validation handler.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function validateAlteredForm(array &$form, FormStateInterface $form_state) {
    // Set up variables.
    $entity = $form_state->getFormObject()->getEntity();
    $path_values = $form_state->getValue('path');
    $domain_path_values = (\Drupal::configFactory()->get('domain_path.settings')->get('hide_path_alias_ui')) ? $form_state->getValue('domain_path') : $path_values[0]['domain_path'];
    $alias = $path_values[0]['alias'] ?? NULL;
    // Check domain access settings if they are on the form.
    $domain_access = [];
    if (!empty($form['field_domain_access']) && !empty($form_state->getValue('field_domain_access'))) {
      $domain_access = \Drupal::service('domain_path.helper')->processDomainAccessField($form_state->getValue('field_domain_access'));
    }
    $build_info = $form_state->getBuildInfo();
    $domain_access_all = empty($form['field_domain_all_affiliates']) || $form_state->getValue('field_domain_all_affiliates')['value'];
    // Validate each path value.
    foreach ($domain_path_values as $domain_id => $domain_path_data) {

      // Don't validate if the domain doesn't have access (we remove aliases
      // for domains that don't have access to this entity).
      $domain_has_access = $domain_access_all || ($domain_access && !empty($domain_access[$domain_id]));
      if (!$domain_has_access) {
        continue;
      }
      // If domain pathauto is not enabled, validate user entered path.
      if (!isset($domain_path_data['pathauto']) || !$domain_path_data['pathauto']) {
        $path = $domain_path_data['path'];
        if (!empty($path) && $path === $alias) {
          $form_state->setError(NestedArray::getValue($form, array_merge($build_info['element_keys'], [$domain_id])), \t('Domain path "%path" matches the default path alias. You may leave the element blank.', ['%path' => $path]));
        }
        elseif (!empty($path)) {
          // Trim slashes and whitespace from end of path value.
          $path_value = rtrim(trim($path), " \\/");

          // Check that the paths start with a slash.
          if ($path_value && $path_value[0] !== '/') {
            $form_state->setError(NestedArray::getValue($form, array_merge($build_info['element_keys'], [$domain_id, 'path'])), \t('Domain path "%path" needs to start with a slash.', ['%path' => $path]));
          }

          // Check for duplicates.
          $entity_query = \Drupal::entityTypeManager()->getStorage('domain_path')
            ->getQuery();
          $entity_query->accessCheck(FALSE);
          $entity_query->condition('domain_id', $domain_id)
            ->condition('alias', $path_value);
          if (!$entity->isNew()) {
            $entity_query->condition('source', '/' . $entity->toUrl()->getInternalPath(), '<>');
          }
          $result = $entity_query->execute();
          if ($result) {
            $form_state->setError(NestedArray::getValue($form, array_merge($build_info['element_keys'], [$domain_id, 'path'])), \t('Domain path %path matches an existing domain path alias', ['%path' => $path_value]));
          }
        }
        if (isset($path_value)) {
          $domain_path_values[$domain_id]['path'] = $path_value;
        }
      }
      $form_state->setValue('domain_path', $domain_path_values);
    }
  }

  /**
   * Submit handler.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException|\Drupal\Core\Entity\EntityStorageException
   */
  public static function submitAlteredEntityForm(array $form, FormStateInterface $form_state) {
    $path_values = $form_state->getValue('path');
    $domain_path_values = (\Drupal::configFactory()->get('domain_path.settings')->get('hide_path_alias_ui')) ? $form_state->getValue('domain_path') : $path_values[0]['domain_path'];
    $entity = $form_state->getFormObject()->getEntity();
    $entity_system_path = '/' . $entity->toUrl()->getInternalPath();
    $properties = [
      'source' => $entity_system_path,
      'language' => $entity->language()->getId(),
    ];
    $domain_access_all = empty($form['field_domain_all_affiliates']) || $form_state->getValue('field_domain_all_affiliates')['value'];
    // Check domain access settings if they are on the form.
    $domain_access = [];
    if (!empty($form['field_domain_access']) && !empty($form_state->getValue('field_domain_access'))) {
      $domain_access = \Drupal::service('domain_path.helper')->processDomainAccessField($form_state->getValue('field_domain_access'));
    }
    // If not set to delete, then save changes.
    if (empty($domain_path_values['domain_path_delete'])) {
      unset($domain_path_values['domain_path_delete']);
      foreach ($domain_path_values as $domain_id => $domain_path_data) {

        $alias = trim($domain_path_data['path']);
        if (isset($domain_path_data['pathauto']) && $domain_path_data['pathauto']) {
          // Generate alias using pathauto.
          $alias = \Drupal::service('domain_path_pathauto.generator')->createEntityAlias($entity, 'return', $domain_id);
          // Remember pathauto default enabled setting.
          \Drupal::service('domain_path_pathauto.generator')->setDomainPathPathautoState($entity, $domain_id, TRUE);
        }
        else {
          // Delete pathauto default enabled setting.
          \Drupal::service('domain_path_pathauto.generator')->deleteDomainPathPathautoState($entity, $domain_id);
        }
        // Get the existing domain path for this domain if it exists.
        $properties['domain_id'] = $domain_id;
        $domain_paths = \Drupal::entityTypeManager()->getStorage('domain_path')
          ->loadByProperties($properties);
        $domain_has_access = $domain_access_all || ($domain_access && !empty($domain_access[$domain_id]));
        $domain_path = $domain_paths ? reset($domain_paths) : NULL;
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
          $domain_path = \Drupal::entityTypeManager()->getStorage('domain_path')
            ->create(['type' => 'domain_path']);
          foreach ($properties_map as $field => $value) {
            $domain_path->set($field, $value);
          }
          $domain_path->save();
        }
        elseif ($domain_path->get('alias')->value !== $alias) {
          $domain_path->set('alias', $alias);
          $domain_path->save();
        }
      }
    }
  }

}
