<?php

namespace Drupal\domain_group_alias\Plugin\DomainGroupSettings;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Utility\Token;
use Drupal\domain_group\Plugin\DomainGroupSettingsBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\domain\DomainValidatorInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\domain\Entity\Domain;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\pathauto\AliasCleanerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides options for group domain alias.
 *
 * @DomainGroupSettings(
 *   id = "domain_group_alias_settings",
 *   label = @Translation("Alias Settings"),
 * )
 */
class GroupDomainAlias extends DomainGroupSettingsBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The domain validator.
   *
   * @var \Drupal\domain\DomainValidatorInterface
   */
  protected $validator;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The webform token manager.
   *
   * @var \Drupal\pathauto\AliasCleanerInterface
   */
  protected $aliasCleaner;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The token replacement instance.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DomainValidatorInterface $validator, RendererInterface $renderer, ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, AliasCleanerInterface $aliasCleaner, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $languageManager, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->validator = $validator;
    $this->renderer = $renderer;
    $this->configFactory = $config_factory;
    $this->aliasManager = $alias_manager;
    $this->aliasCleaner = $aliasCleaner;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $languageManager;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('domain.validator'),
      $container->get('renderer'),
      $container->get('config.factory'),
      $container->get('path_alias.manager'),
      $container->get('pathauto.alias_cleaner'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, GroupInterface $group) {
    $domain = Domain::load('group_' . $group->id());
    $config = $this->configFactory->get('domain_site_settings.domainconfigsettings');
    $variables['options']['attributes']['class'][] = 'token-dialog';
    $variables['options']['attributes']['class'][] = 'use-ajax';
    $query_options = [
      'token_types' => [
        'group_relationship',
      ],
      'global_types' => TRUE,
      'click_insert' => FALSE,
      'show_restricted' => FALSE,
      'show_nested' => FALSE,
      'recursion_limit' => 3,
    ];
    $variables['options']['query']['options'] = Json::encode($query_options);
    $variables['options']['attributes'] += [
      'data-dialog-type' => 'dialog',
      'data-dialog-options' => Json::encode([
        'dialogClass' => 'token-tree-dialog',
        'width' => 600,
        'height' => 400,
        'position' => ['my' => 'right bottom', 'at' => 'right-10 bottom-10'],
        'draggable' => TRUE,
        'autoResize' => FALSE,
      ]),
    ];

    foreach ($group->getGroupType()->getInstalledContentPlugins() as $plugin) {
      if ($plugin->getEntityTypeId() == 'node') {
        $form[$plugin->getEntityBundle()] = [
          '#type' => 'textfield',
          '#title' => $this->t('Alias pattern for @bundle', ['@bundle' => $plugin->getLabel()]),
          '#element_validate' => [
            'token_element_validate',
            'pathauto_pattern_validate',
          ],
          '#after_build' => ['token_element_validate'],
          '#token_types' => ['group_relationship'],
          '#min_tokens' => 1,
          '#default_value' => (isset($domain) && $config->get($domain->id() . '_alias_' . $plugin->getEntityBundle()) !== NULL) ? $config->get($domain->id() . '_alias_' . $plugin->getEntityBundle()) : '/[group_relationship:node:title]',
        ];
      }
    }
    $form['link'] = Link::createFromRoute(t('Browse available tokens.'), 'token.tree', [], $variables['options'])->toRenderable();
    $form_state->set('group', $group);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $group = $form_state->get('group');
    foreach ($group->getGroupType()->getInstalledContentPlugins() as $plugin) {
      if ($plugin->getEntityTypeId() == 'node') {
        $alias = rtrim(trim($form_state->getValue($plugin->getEntityBundle())), " \\/");
        if ($alias && $alias[0] !== '/') {
          $form_state->setErrorByName($plugin->getEntityBundle(), t('The alias needs to start with a slash.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\Group $group */
    $group = $form_state->get('group');
    $gid = $group->id();
    if ($domain = Domain::load('group_' . $gid)) {
      $lang = $this->languageManager->getCurrentLanguage()->getId();
      $domain_id = $domain->id();

      $opts = [
        'clear' => TRUE,
        'callback' => [$this->aliasCleaner, 'cleanTokenValues'],
        'langcode' => $lang,
        'pathauto' => TRUE,
      ];
      // Set configuration.
      $config = $this->configFactory->getEditable('domain_site_settings.domainconfigsettings');
      foreach ($group->getGroupType()->getInstalledContentPlugins() as $plugin) {
        if ($plugin->getEntityTypeId() == 'node') {
          $content_entities = $group->getContent('group_node:' . $plugin->getEntityBundle());
          $explored_bundle = explode('-', $plugin->getEntityBundle());
          $bundle_val = end($explored_bundle);
          $token_value = $form_state->getValue($bundle_val);
          // Check entities.
          if ($content_entities && $token_value) {
            foreach ($content_entities as $content_entity) {
              $bubbleable_metadata = BubbleableMetadata::createFromObject($content_entity);
              $domain_alias_token = $this->token->replace($token_value, ['group_relationship' => $content_entity], $opts, $bubbleable_metadata);
              $gc_path = '/group/' . $gid . '/content/' . $content_entity->id();
              $properties = [
                'source' => $gc_path,
                'domain_id' => $domain_id,
                'language' => $lang,
              ];
              $domain_alias = $this->entityTypeManager
                ->getStorage('domain_path')
                ->loadByProperties($properties);
              // Check domain to update or create a new one.
              if ($domain_alias) {
                /** @var \Drupal\domain_alias\Entity\DomainAlias $domain_alias */
                $domain_alias = reset($domain_alias);
                $domain_alias->set('alias', $domain_alias_token);
                $domain_alias->save();
              }
              // Create domain path.
              else {
                $domain_path_storage = $this->entityTypeManager->getStorage('domain_path');
                $domain_path_entity = $domain_path_storage->create(['type' => 'domain_path']);
                $domain_specific_alias_path = !empty($domain_alias_token) ? $domain_alias_token : $gc_path;
                $domain_path_entity->set('alias', $domain_specific_alias_path);
                $domain_path_entity->set('domain_id', $domain_id);
                $domain_path_entity->set('language', $content_entity->language()->getId());
                $domain_path_entity->set('source', $gc_path);
                $domain_path_entity->save();
              }
            }
          }
          $value = $form_state->getValue($plugin->getEntityBundle());
          $config->set($domain_id . '_alias_' . $plugin->getEntityBundle(), $value);
        }
      }
      $config->save();
    }
  }

}
