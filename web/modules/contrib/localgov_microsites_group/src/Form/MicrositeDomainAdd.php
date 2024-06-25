<?php

namespace Drupal\localgov_microsites_group\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\domain\Form\DomainForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add domain for microsite group.
 */
class MicrositeDomainAdd extends DomainForm {

  /**
   * The private store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * Default content generator.
   *
   * @var \Drupal\localgov_microsites_group\GroupDefaultContentInterface
   */
  protected $defaultContent;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);
    $form->privateTempStoreFactory = $container->get('tempstore.private');
    $form->defaultContent = $container->get('localgov_microsites_group.default_content');
    $form->configFactory = $container->get('config.factory');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // Going back and returning with a stored hostname/id the field will be
    // disabled. We know on creating a new microsite it should always be
    // enabled.    $form['id']['#disabled'] = FALSE;.
    $wizard_id = $form_state->get('group_wizard_id');
    $store = $this->privateTempStoreFactory->get($wizard_id);
    $store_id = $form_state->get('store_id');
    $group = $store->get("$store_id:entity");

    // May as well hide the name field.
    $form['name'] = [
      '#type' => 'value',
      '#value' => $form['name']['#default_value'],
    ];
    // And the weight.
    $form['weight'] = [
      '#type' => 'value',
      '#value' => $form['weight']['#default_value'],
    ];
    // Never going to be the default domain.
    $form['is_default'] = [
      '#type' => 'value',
      '#value' => FALSE,
    ];

    // Site email, only field from site settings we don't generate.
    $form['site_mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Site email address'),
      '#default_value' => $group->getOwner()->getEmail(),
      '#description' => $this->t("The <em>From</em> address in automated emails sent during registration and new password requests, and other notifications. (Use an address ending in your site's domain to help prevent this email being flagged as spam.)"),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $wizard_id = $form_state->get('group_wizard_id');
    $store = $this->privateTempStoreFactory->get($wizard_id);
    $store_id = $form_state->get('store_id');
    $group = $store->get("$store_id:entity");
    $replace = [
      '@group' => $group->label(),
    ];

    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Complete: Create @group and domain', $replace),
      '#submit' => ['::submitForm', '::complete'],
    ];

    $actions['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#submit' => ['::back'],
      '#limit_validation_errors' => [],
    ];

    $form['actions'] = $actions;

    return $actions;
  }

  /**
   * Complete the wizard. Save all the entities.
   */
  public function complete(array &$form, FormStateInterface $form_state) {
    $wizard_id = $form_state->get('group_wizard_id');
    $store = $this->privateTempStoreFactory->get($wizard_id);
    $store_id = $form_state->get('store_id');
    $group = $store->get("$store_id:entity");
    $group_bundle = $this->entityTypeManager
      ->getStorage('group_type')
      ->load($group->bundle());
    $membership = $store->get("$store_id:membership");

    // Replicate the form from step 1 and call the save method.
    $form_object = $this->entityTypeManager->getFormObject($group->getEntityTypeId(), 'new_domain');
    $form_object->setEntity($group);
    $form_object->save($form, $form_state);

    if ($membership) {
      $form_object = $this->entityTypeManager->getFormObject($membership->getEntityTypeId(), 'new_domain');
      $membership->set('gid', $group->id());
      $form_object->setEntity($membership);
      $form_object->save($form, $form_state);
    }
    elseif ($group_bundle->creatorGetsMembership()) {
      $values = ['group_roles' => $group_bundle->getCreatorRoleIds()];
      $group->addMember($group->getOwner(), $values);
    }

    // Save the domain.
    $this->entity->setThirdPartySetting('group_context_domain', 'group_uuid', $group->uuid());
    parent::save($form, $form_state);

    // Initial site settings configuration overrides.
    $config_override = $this->configFactory()->getEditable('domain.config.' . $this->entity->id() . '.system.site');
    if ($front_page = $this->defaultContent->generate($group)) {
      $config_override->set('page.front', $front_page->toUrl()->toString());
    }
    $config_override->set('name', $group->label());
    $config_override->set('mail', $form_state->getValue('site_mail'));
    $config_override->save();

    // We also clear the temp store so we can start fresh next time around.
    $store->delete("$store_id:step");
    $store->delete("$store_id:entity");
    $store->delete("$store_id:membership");
    $store->delete("$store_id:domain");

    $form_state->setRedirect('view.localgov_microsites_overview.collection');
  }

  /**
   * Goes back to previous step of the creation wizard.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\group\Entity\Controller\GroupContentController::createForm()
   */
  public function back(array &$form, FormStateInterface $form_state) {

    $store = $this->privateTempStoreFactory->get($form_state->get('group_wizard_id'));
    $store_id = $form_state->get('store_id');
    $group = $store->get("$store_id:entity");
    $group_bundle = $this->entityTypeManager
      ->getStorage('group_type')
      ->load($group->bundle());
    $membership_step = $group_bundle->creatorMustCompleteMembership();
    $store->set("$store_id:step", $membership_step ? 2 : 1);

    // Store this domain.
    $store->set("$store_id:domain", $this->getEntity());

    // Disable any URL-based redirect when going back to the previous step.
    $request = $this->getRequest();
    $form_state->setRedirect('<current>', [], ['query' => $request->query->all()]);
    $request->query->remove('destination');
  }

  /**
   * Store entity and move to next step.
   */
  public function store(array &$form, FormStateInterface $form_state) {
    $store = $this->privateTempStoreFactory->get($form_state->get('group_wizard_id'));
    $store_id = $form_state->get('store_id');

    $store->set("$store_id:membership", $this->getEntity());
    $store->set("$store_id:step", 3);

    // Disable any URL-based redirect until the final step.
    $request = $this->getRequest();
    $form_state->setRedirect('<current>', [], ['query' => $request->query->all()]);
    $request->query->remove('destination');
  }

}
