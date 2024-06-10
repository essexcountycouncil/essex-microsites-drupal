<?php

namespace Drupal\domain_group\Plugin\DomainGroupSettings;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\domain\DomainStorageInterface;
use Drupal\domain_group\Plugin\DomainGroupSettingsBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\domain\DomainValidatorInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\domain\Entity\Domain;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupAccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides options for group domain.
 *
 * @DomainGroupSettings(
 *   id = "domain_group_domain",
 *   label = @Translation("Domain"),
 * )
 */
class GroupDomain extends DomainGroupSettingsBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

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
   * The domain entity storage.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * The domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * The language manager.
   *
   * @var \Drupal\language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DomainValidatorInterface $validator, RendererInterface $renderer, ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, DomainStorageInterface $domain_storage, DomainNegotiatorInterface $domain_negotiator, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->validator = $validator;
    $this->renderer = $renderer;
    $this->configFactory = $config_factory;
    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
    $this->domainStorage = $domain_storage;
    $this->domainNegotiator = $domain_negotiator;
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
      $container->get('domain.validator'),
      $container->get('renderer'),
      $container->get('config.factory'),
      $container->get('path_alias.manager'),
      $container->get('path.validator'),
      $container->get('entity_type.manager')->getStorage('domain'),
      $container->get('domain.negotiator'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(GroupInterface $group, AccountInterface $account) {
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'administer group domain settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, GroupInterface $group) {
    $domain = Domain::load('group_' . $group->id());

    // Domain settings.
    $form['hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#size' => 40,
      '#maxlength' => 80,
      '#description' => $this->t('The canonical hostname, using the full <em>subdomain.example.com</em> format. Leave off the http:// and the trailing slash and do not include any paths.<br />If this domain uses a custom http(s) port, you should specify it here, e.g.: <em>subdomain.example.com:1234</em><br />The hostname may contain only lowercase alphanumeric characters, dots, dashes, and a colon (if using alternative ports).'),
      '#default_value' => isset($domain) ? $domain->getHostname() : '',
    ];
    if ($domain) {
      $form['empty_host_message'] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            ':input[name="hostname"]' => ['empty' => TRUE],
          ],
        ],
        'empty_host' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t('Leaving empty the hostname will invalidate all the configutation in this form.'),
          '#attributes' => [
            'class' => ['color-error'],
          ],
        ],
      ];
    }
    $form['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Domain status'),
      '#options' => [1 => $this->t('Active'), 0 => $this->t('Inactive')],
      '#description' => $this->t('"Inactive" domains are only accessible to user roles with that assigned permission.'),
      '#default_value' => isset($domain) ? (int) $domain->get('status') : TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Avoid group domains if default domain does not exist.
    if (!$this->domainStorage->loadDefaultDomain()) {
      $form_state->setErrorByName('hostname', $this->t('In order to enable this Organization domain, a Default one should be set. Please go to the <a href="@url">Domain records</a> page and create a Default Domain for the main site <i>(@host_name)</i>.', [
        '@url' => '/admin/config/domain',
        '@host_name' => $this->domainNegotiator->getHttpHost(),
      ]));
    }

    $hostname = $form_state->getValue('hostname');
    if ($hostname) {
      $errors = $this->validator->validate($hostname);
      $existing = $this->domainStorage->loadByProperties(['hostname' => $hostname]);
      $existing = reset($existing);
      // If we have already registered a hostname,
      // make sure we don't create a duplicate.
      $group = $form_state->get('group');
      if ($existing && $existing->id() != 'group_' . $group->id() && $existing->getDomainId() != $hostname) {
        $form_state->setErrorByName('hostname', $this->t('The hostname is already registered.'));
      }
      if (!empty($errors)) {
        // Render errors to display as message.
        $message = [
          '#theme' => 'item_list',
          '#items' => $errors,
        ];
        $message = $this->renderer->renderPlain($message);
        $form_state->setErrorByName('hostname', $message);
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $group = $form_state->get('group');
    if ($form_state->getValue('hostname')) {
      if ($domain = Domain::load('group_' . $group->id())) {
        $domain->setHostname($form_state->getValue('hostname'));
        $domain->set('status', $form_state->getValue('status'));
      }
      else {
        $domain = Domain::create([
          'id' => 'group_' . $group->id(),
          'name' => $group->label(),
          'hostname' => $form_state->getValue('hostname'),
          'scheme' => 'variable',
          'status' => $form_state->getValue('status'),
          'is_default' => FALSE,
        ]);
      }
      $domain->setThirdPartySetting('domain_group', 'group', $group->id());
      $domain->save();
    }
    else {
      if ($domain = Domain::load('group_' . $group->id())) {
        $domain->delete();
      }
    }
  }

}
