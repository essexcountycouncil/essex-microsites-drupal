<?php

namespace Drupal\localgov_microsites_group\Plugin\DomainGroupSettings;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\domain\DomainInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;
use Drupal\localgov_microsites_group\DomainFromGroupTrait;
use Drupal\localgov_microsites_group\Plugin\DomainGroupSettingsBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides options for group domain.
 *
 * @DomainGroupSettings(
 *   id = "localgov_microsites_theme_settings",
 *   label = @Translation("Theme override"),
 * )
 */
class ThemeSettings extends DomainGroupSettingsBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;
  use DomainFromGroupTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, ThemeHandlerInterface $theme_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(GroupInterface $group, AccountInterface $account) {
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'set localgov microsite theme override');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, GroupInterface $group) {
    $config_override = NULL;
    if (!$group->isNew() && $domain = $this->getDomainFromGroup($group)) {
      $config_override = $this->loadConfigOverride($domain);
      if ($config_override->isNew()) {
        $config_override = NULL;
      }
    }

    // By retrieving editable here we get the non-overriden version.
    $site_config = $this->configFactory->getEditable('system.theme');
    $site_default = $site_config->get('default');
    $site_admin = $site_config->get('admin');

    // Get all available themes.
    $themes = $this->themeHandler->rebuildThemeData();
    $admin_options = [
      '' => $this->t('No override (:site_admin)',
        [':site_admin' => $themes[$site_admin]->info['name'] ?? '']
      ),
    ];
    $default_options = [
      '' => $this->t('No override (:site_default)',
        [':site_default' => $themes[$site_default]->info['name'] ?? '']
      ),
    ];
    // Remove obsolete themes.
    $themes = array_filter($themes, function ($theme) {
      return !$theme->isObsolete();
    });
    uasort($themes, [ThemeExtensionList::class, 'sortByName']);
    foreach ($themes as &$theme) {
      if (!empty($theme->status)) {
        if ($theme->getName() != $site_default) {
          $default_options[$theme->getName()] = $theme->info['name'];
        }
        if ($theme->getName() != $site_admin) {
          $admin_options[$theme->getName()] = $theme->info['name'];
        }
      }
    }

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>The default theme is LocalGov Microsites Base, which can be customised extensively through the UI on the Site design tab of the microsite. If you need further customisation we recommend making a child theme of LocalGov Microsites Base.</p>',
    ];

    $default = $config_override ? $config_override->get('default') : '';
    $form['default'] = [
      '#type' => 'select',
      '#title' => $this->t('Default theme'),
      '#default_value' => $default,
      '#description' => $this->t("Override the theme used on this microsite."),
      '#options' => $default_options,
    ];
    $admin = $config_override ? $config_override->get('admin') : '';
    $form['admin'] = [
      '#type' => 'select',
      '#title' => $this->t('Admin theme'),
      '#default_value' => $admin,
      '#description' => $this->t("Override theme used for administration pages."),
      '#options' => $admin_options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $group = $form_state->get('group');
    $domain = $this->getDomainFromGroup($group);
    $config_override = $this->loadConfigOverride($domain);

    foreach (['admin', 'default'] as $field_name) {
      $value = $form_state->getValue($field_name);
      if (empty($value)) {
        $config_override->clear($field_name);
      }
      else {
        $config_override->set($field_name, $value);
      }
    }
    $config_override->save();
  }

  /**
   * Load, or create, domain config override - for language.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain site configuration being overriden.
   *
   * @return \Drupal\Core\Config\Config
   *   Editable configuaration.
   */
  private function loadConfigOverride(DomainInterface $domain) {
    if ($this->languageManager->getConfigOverrideLanguage() == $this->languageManager->getDefaultLanguage()) {
      $config_id = 'domain.config.' . $domain->id() . '.system.theme';
    }
    else {
      $config_id = 'domain.config.' . $domain->id() . '.' . $this->languageManager->getConfigOverrideLanguage() . '.system.theme';
    }
    $config_override = $this->configFactory->getEditable($config_id);

    return $config_override;
  }

}
