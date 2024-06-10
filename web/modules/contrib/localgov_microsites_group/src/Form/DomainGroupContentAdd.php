<?php

namespace Drupal\localgov_microsites_group\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Form\GroupContentForm;

/**
 * Add content to domain group.
 */
class DomainGroupContentAdd extends GroupContentForm {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    // If we are on step 2 of a wizard, we need to alter the actions.
    if ($form_state->get('group_wizard')) {
      $wizard_id = $form_state->get('group_wizard_id');
      $store = $this->privateTempStoreFactory->get($wizard_id);
      $store_id = $form_state->get('store_id');

      if ($wizard_id == 'domain_group_add' && $store->get("$store_id:step") === 2) {
        // Add a back button to return to step 1 with.
        $actions['back'] = [
          '#type' => 'submit',
          '#value' => $this->t('Back'),
          '#submit' => ['::back'],
          '#limit_validation_errors' => [],
        ];

        // Make the label of the save button more intuitive.
        if ($wizard_id == 'domain_group_add') {
          $actions['submit']['#value'] = $this->t('Next: configure domain');
        }

        // Make sure we complete the wizard before saving.
        $actions['submit']['#submit'] = ['::submitForm', '::store'];
      }
    }

    return $actions;
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
