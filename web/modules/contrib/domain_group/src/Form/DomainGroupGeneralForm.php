<?php

namespace Drupal\domain_group\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\domain\DomainStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * General Settins Form.
 */
class DomainGroupGeneralForm extends ConfigFormBase {

  /**
   * The domain entity storage.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * DomainGroupGeneralForm constructor.
   *
   * @param Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Config Factory.
   * @param Drupal\domain\DomainStorageInterface $domain_storage
   *   The domain entity storage.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DomainStorageInterface $domain_storage) {
    parent::__construct($config_factory);
    $this->domainStorage = $domain_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getStorage('domain')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['domain_group.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_group_general_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('domain_group.settings');
    $form['unique_group_access'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unique group access'),
      '#description' => $this->t('Only the group for the active domain will be accessible (Group nodes included). if you change this setting we recommend to <a href=":rebuild">Rebuild permissions</a>.', [
        ':rebuild' => Url::fromRoute('node.configure_rebuild_confirm')->toString(),
      ]),
      '#default_value' => $config->get('unique_group_access'),
    ];
    $form['restricted_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Restrict login to Default Domain'),
      '#description' => $this->t('Login form will only be accessible for the Default Domain. <br/>If checked, we will redirect all /user/login requests to the Default Domain.'),
      '#default_value' => $config->get('restricted_login'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Set error if no Default Domain is set.
    if (!empty($values['restricted_login']) && !$this->domainStorage->loadDefaultDomain()) {
      $toggle_url = Url::fromRoute('domain.admin')->toString();
      $form_state->setErrorByName('restricted_login', $this->t('In order to enable Restricted Login, a Default domain should be set. Please go to the <a href=":url">Domain records</a> page and create a Default Domain for the main site.', [
        ':url' => $toggle_url,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('domain_group.settings')
      ->set('unique_group_access', $form_state->getValue('unique_group_access'))
      ->set('restricted_login', $form_state->getValue('restricted_login'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
