<?php

namespace Drupal\localgov_microsites_group\Plugin\DomainGroupSettings;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\domain\DomainInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;
use Drupal\localgov_microsites_group\DomainFromGroupTrait;
use Drupal\localgov_microsites_group\Plugin\DomainGroupSettingsBase;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides options for group domain.
 *
 * @DomainGroupSettings(
 *   id = "domain_group_site_settings",
 *   label = @Translation("Site Settings"),
 * )
 */
class SiteSettings extends DomainGroupSettingsBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;
  use DomainFromGroupTrait;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The domain entity storage.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
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
      $container->get('path_alias.manager'),
      $container->get('path.validator'),
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(GroupInterface $group, AccountInterface $account) {
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'administer group domain site settings');
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

    $site_config = $this->configFactory->get('system.site');

    $site_name = $config_override ? $config_override->get('name') : $group->label();
    $form['site_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site name'),
      '#default_value' => $site_name,
      '#required' => TRUE,
    ];
    $site_slogan = $config_override ? $config_override->get('slogan') : $site_config->get('slogan');
    $form['site_slogan'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Slogan'),
      '#default_value' => $site_slogan,
      '#description' => $this->t("How this is used depends on your site's theme."),
    ];
    $site_mail = $config_override && $config_override->get('mail') ? $config_override->get('mail') : $group->getOwner()->getEmail();
    $form['site_mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Site email address'),
      '#default_value' => $site_mail,
      '#description' => $this->t("The <em>From</em> address in automated emails sent during registration and new password requests, and other notifications. (Use an address ending in your site's domain to help prevent this email being flagged as spam.)"),
      '#required' => TRUE,
    ];
    $site_frontpage = $config_override ? $config_override->get('page.front') : ($group->isNew() ? '' : $group->toUrl()->toString());
    $form['site_frontpage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site front page'),
      '#default_value' => $site_frontpage,
      '#required' => FALSE,
    ];
    $form['error_page'] = [
      '#type' => 'details',
      '#title' => $this->t('Error pages'),
      '#open' => TRUE,
    ];
    $site_403 = $config_override && $config_override->get('page.403') ? $config_override->get('page.403') : $site_config->get('page.403');
    $form['error_page']['site_403'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default 403 (access denied) page'),
      '#default_value' => $site_403,
      '#size' => 40,
      '#description' => $this->t('This page is displayed when the requested document is denied to the current user. Leave blank to display a generic "access denied" page.'),
    ];
    $site_404 = $config_override && $config_override->get('page.404') ? $config_override->get('page.404') : $site_config->get('page.404');
    $form['error_page']['site_404'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default 404 (not found) page'),
      '#default_value' => $site_404,
      '#size' => 40,
      '#description' => $this->t('This page is displayed when no other content matches the requested document. Leave blank to display a generic "page not found" page.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

    // Get the normal paths of both error pages.
    if (!$form_state->isValueEmpty('site_403')) {
      $form_state->setValueForElement($form['domain_group_site_settings']['error_page']['site_403'], $this->aliasManager->getPathByAlias($form_state->getValue('site_403')));
    }
    if (!$form_state->isValueEmpty('site_404')) {
      $form_state->setValueForElement($form['domain_group_site_settings']['error_page']['site_404'], $this->aliasManager->getPathByAlias($form_state->getValue('site_404')));
    }
    if (($value = $form_state->getValue('site_403')) && $value[0] !== '/') {
      $form_state->setErrorByName('site_403', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('site_403')]));
    }
    if (($value = $form_state->getValue('site_404')) && $value[0] !== '/') {
      $form_state->setErrorByName('site_404', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('site_404')]));
    }
    // Validate 403 error path.
    if (!$form_state->isValueEmpty('site_403') && !$this->pathValidator->isValid($form_state->getValue('site_403'))) {
      $form_state->setErrorByName('site_403', $this->t("The path '%path' is either invalid or you do not have access to it.", ['%path' => $form_state->getValue('site_403')]));
    }
    // Validate 403 for restrict_login.
    $restricted_login = $this->configFactory->getEditable('domain_group.settings')->get('restricted_login');
    // If restricted login is set, avoid 403 page to redirect to /user/login.
    if (!$form_state->isValueEmpty('site_403') && $form_state->getValue('site_403') == '/user/login' && $restricted_login) {
      // General settings config form with query to get the Admins back here.
      $settings_page = Url::fromRoute('domain_group.domain_group_general_form', [], [
        'query' => [
          'destination' => Url::fromRoute('<current>')->toString(),
        ],
      ])->toString();
      $form_state->setErrorByName('site_403', $this->t("Redirecting 403 to login page is not supported by the Restricted Login setting.<br />
        Consider leaving this field blank or disable the Restricted Login in the <a href=':general_settings_page'>General Settings form</a>.", [
          ':general_settings_page' => $settings_page,
        ]));
    }
    // Validate 404 error path.
    if (!$form_state->isValueEmpty('site_404') && !$this->pathValidator->isValid($form_state->getValue('site_404'))) {
      $form_state->setErrorByName('site_404', $this->t("The path '%path' is either invalid or you do not have access to it.", ['%path' => $form_state->getValue('site_404')]));
    }
    // Validate homepage.
    if (!$form_state->isValueEmpty('site_frontpage') && !$this->pathValidator->isValid($form_state->getValue('site_frontpage'))) {
      $form_state->setErrorByName('site_frontpage', $this->t("The path '%path' is either invalid or you do not have access to it.", ['%path' => $form_state->getValue('site_frontpage')]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $group = $form_state->get('group');
    $domain = $this->getDomainFromGroup($group);
    $config_override = $this->loadConfigOverride($domain);
    // Get editable to avoid getting overridden config.
    $site_config = $this->configFactory->getEditable('system.site');

    $fields = [
      'site_name' => 'name',
      'site_slogan' => 'slogan',
      'site_mail' => 'mail',
      'site_frontpage' => 'page.front',
      'site_403' => 'page.403',
      'site_404' => 'page.404',
    ];
    foreach ($fields as $field_name => $config_key) {
      $value = $form_state->getValue($field_name);
      if ($value == $site_config->get($config_key)) {
        $config_override->clear($config_key);
      }
      else {
        $config_override->set($config_key, $value);
      }
    }
    $config_override->save();
  }

  /**
   * Load, or create, domain config override - for language.
   *
   * @todo by creating config that's not defined as a config entity this way
   * it's not getting a uuid, and it won't have a schema. Partly this will be an
   * issue with the module, collections would seem the obvious move, but maybe
   * there's a better way of creating the new config at least. It's not
   * simpulating an import though, because it doesn't have an original state to
   * hash.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain site configuration being overriden.
   *
   * @return \Drupal\Core\Config\Config
   *   Editable configuaration.
   */
  private function loadConfigOverride(DomainInterface $domain) {
    if ($this->languageManager->getConfigOverrideLanguage() == $this->languageManager->getDefaultLanguage()) {
      $config_id = 'domain.config.' . $domain->id() . '.system.site';
    }
    else {
      $config_id = 'domain.config.' . $domain->id() . '.' . $this->languageManager->getConfigOverrideLanguage() . '.system.site';
    }
    $config_override = $this->configFactory->getEditable($config_id);

    return $config_override;
  }

}
