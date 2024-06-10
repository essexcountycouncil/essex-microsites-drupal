<?php

namespace Drupal\group_sites\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\group_sites\GroupSitesAccessPolicyRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for the group_sites module.
 */
class GroupSitesSettingsForm extends ConfigFormBase {

  public function __construct(
    protected ContextRepositoryInterface $contextRepository,
    protected ContextHandlerInterface $contextHandler,
    protected GroupSitesAccessPolicyRepositoryInterface $accessPolicyRepository,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('context.repository'),
      $container->get('context.handler'),
      $container->get('group_sites.access_policy_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_sites_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['group_sites.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('group_sites.settings');

    $definition = EntityContextDefinition::fromEntityTypeId('group');
    $contexts = $this->contextRepository->getAvailableContexts();
    $contexts = $this->contextHandler->getMatchingContexts($contexts, $definition);

    $form['context_provider'] = [
      '#type' => 'radios',
      '#title' => $this->t('Context provider'),
      '#description' => $this->t("The context provider will return a group that represents the active site.<br /><em>Warning</em>: Using context providers that don't always return a group context is ill-advised.<br />The \"Group from URL\" context that ships with the Group module should therefore ideally not be used."),
      '#default_value' => $config->get('context_provider'),
      '#required' => TRUE,
      '#options' => [],
    ];

    foreach ($contexts as $context_id => $context) {
      $context_definition = $context->getContextDefinition();
      $form['context_provider']['#options'][$context_id] = $context_definition->getLabel();
      if ($description = $context_definition->getDescription()) {
        $form['context_provider'][$context_id]['#description'] = $description;
      }
    }

    $form['no_site_access_policy'] = [
      '#type' => 'radios',
      '#title' => $this->t('Access policy when there is no group'),
      '#description' => $this->t('What to do when the context provider does not return a Group entity.<br /><em>Note</em>: This should be avoided at all cost to ensure a consistent experience, but Group Sites needs to know what to do if a context provider fails to provide a Group entity..'),
      '#default_value' => $config->get('no_site_access_policy'),
      '#required' => TRUE,
      '#options' => [],
    ];

    foreach ($this->accessPolicyRepository->getNoSiteAccessPolicies() as $service_id => $access_policy) {
      $form['no_site_access_policy']['#options'][$service_id] = $access_policy->getLabel();
      if ($description = $access_policy->getDescription()) {
        $form['no_site_access_policy'][$service_id]['#description'] = $description;
      }
    }

    $form['site_access_policy'] = [
      '#type' => 'radios',
      '#title' => $this->t('Access policy when there is a group'),
      '#description' => $this->t('How to leverage the context provided Group to manage site access.'),
      '#default_value' => $config->get('site_access_policy'),
      '#required' => TRUE,
      '#options' => [],
    ];

    foreach ($this->accessPolicyRepository->getSiteAccessPolicies() as $service_id => $access_policy) {
      $form['site_access_policy']['#options'][$service_id] = $access_policy->getLabel();
      if ($description = $access_policy->getDescription()) {
        $form['site_access_policy'][$service_id]['#description'] = $description;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('group_sites.settings');
    $config_has_changed = FALSE;

    $conf_context_provider = $config->get('context_provider');
    $form_context_provider = $form_state->getValue('context_provider');
    if ($conf_context_provider !== $form_context_provider) {
      $config_has_changed = TRUE;
      $config->set('context_provider', $form_context_provider)->save();
    }

    $conf_no_site_access_policy = $config->get('no_site_access_policy');
    $form_no_site_access_policy = $form_state->getValue('no_site_access_policy');
    if ($conf_no_site_access_policy !== $form_no_site_access_policy) {
      $config_has_changed = TRUE;
      $config->set('no_site_access_policy', $form_no_site_access_policy)->save();
    }

    $conf_site_access_policy = $config->get('site_access_policy');
    $form_site_access_policy = $form_state->getValue('site_access_policy');
    if ($conf_site_access_policy !== $form_site_access_policy) {
      $config_has_changed = TRUE;
      $config->set('site_access_policy', $form_site_access_policy)->save();
    }

    if ($config_has_changed) {
      $config->save();
    }

    parent::submitForm($form, $form_state);
  }

}
