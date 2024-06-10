<?php

namespace Drupal\localgov_microsites_group\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Form\GroupForm;

/**
 * Add a domain to group form.
 */
class DomainGroupAdd extends GroupForm {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $group_type = $this->getEntity()->getGroupType();
    $replace = ['@group_type' => $group_type->label()];

    if ($this->operation == 'new_domain') {
      $actions['submit']['#submit'] = ['::submitForm', '::store'];

      if ($group_type->creatorMustCompleteMembership()) {
        $actions['submit']['#value'] = $this->t('Next: complete @group_type membership', $replace);
      }
      // Update the label if we are not using the wizard, but the group creator
      // still gets a membership upon group creation.
      elseif ($group_type->creatorGetsMembership()) {
        $actions['submit']['#value'] = $this->t('Next: configure @group_type domain', $replace);
      }
      // Use a simple submit label if none of the above applies.
      else {
        $actions['submit']['#value'] = $this->t('Next: configure @group_type domain', $replace);
      }

      // Add a cancel button to clear the private temp store. This exits the
      // wizard without saving.
      $actions['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
        '#submit' => ['::cancel'],
        '#limit_validation_errors' => [],
      ];
    }

    return $actions;
  }

  /**
   * Cancels the wizard for group creator membership.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\group\Entity\Controller\GroupController::addForm()
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $store = $this->privateTempStoreFactory->get($form_state->get('group_wizard_id'));
    $store_id = $form_state->get('store_id');
    $store->delete("$store_id:entity");
    $store->delete("$store_id:membership");
    $store->delete("$store_id:step");

    // Redirect to the front page if no destination was set in the URL.
    $form_state->setRedirect('<front>');
  }

}
