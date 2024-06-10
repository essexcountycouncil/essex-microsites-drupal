<?php

namespace Drupal\localgov_microsites_group\Form;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\localgov_microsites_group\Plugin\DomainGroupSettingsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Domain Group Settings Form.
 */
class DomainGroupSettingsForm extends FormBase {

  /**
   * The DomainGroupSettingsManager service.
   *
   * @var \Drupal\localgov_microsites_group\Plugin\DomainGroupSettingsManager
   */
  protected $pluginManagerDomainGroupSettings;

  /**
   * Constructs a new DomainGroupSettingsForm object.
   */
  public function __construct(DomainGroupSettingsManager $plugin_manager_domain_group_settings) {
    $this->pluginManagerDomainGroupSettings = $plugin_manager_domain_group_settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.domain_group_settings')
    );
  }

  /**
   * Check route access.
   *
   * Built from all plugins. If any allow access the account can access the
   * form. Only those plugins that are allowed will be shown on the form.
   */
  public function access(GroupInterface $group, AccountInterface $account) {
    $access = AccessResultAllowed::allowedIfHasPermission($account, 'bypass domain group permissions');
    foreach ($this->pluginManagerDomainGroupSettings->getAll() as $plugin) {
      $access = $access->orIf($plugin->access($group, $account));
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_group_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, GroupInterface $group = NULL) {
    $form_state->set('group', $group);

    $form['tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('@group_label - Domain Settings', ['@group_label' => $group->label()]),
    ];
    foreach ($this->getPluginsWithAccess($group) as $plugin_id => $plugin) {
      $form[$plugin_id] = [
        '#type' => 'details',
        '#title' => $plugin->getLabel(),
        '#group' => 'tabs',
      ] + $plugin->buildConfigurationForm([], $form_state, $group);
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->getPluginsWithAccess($form_state->get('group')) as $plugin) {
      $plugin->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->getPluginsWithAccess($form_state->get('group')) as $plugin) {
      $plugin->submitConfigurationForm($form, $form_state);
    }
    $this->messenger()->addStatus('Changes saved');
  }

  /**
   * Yield plugins the current user has access to.
   */
  protected function getPluginsWithAccess(GroupInterface $group) {
    $account = $this->currentUser();
    foreach ($this->pluginManagerDomainGroupSettings->getAll() as $plugin_id => $plugin) {
      $plugin_access = $plugin->access($group, $account);
      if ($account->hasPermission('bypass domain group permissions') ||
        $plugin_access->isAllowed()
      ) {
        yield $plugin_id => $plugin;
      }
    }
  }

}
