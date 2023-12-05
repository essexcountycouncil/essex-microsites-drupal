<?php

namespace Drupal\domain_path\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\path\Form\PathFilterForm;

class DomainPathFilterForm extends PathFilterForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.domain_path.collection', [], [
      'query' => ['search' => trim($form_state->getValue('filter'))],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.domain_path.collection');
  }

}
