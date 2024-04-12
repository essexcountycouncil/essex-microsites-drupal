<?php

namespace Drupal\ecc_waste\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure ECC Waste settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ecc_waste_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ecc_waste.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['osdata_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OS Data API Key'),
      '#default_value' => $this->config('ecc_waste.settings')->get('osdata_api_key'),
      '#description' => $this->t('Postcode lookup uses the OS Data places API, for which you need an API key.'),
    ];
    $microsites = [];
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $group_entity_type = $entity_type_manager->getDefinition('group');
    if ($group_entity_type) {
      $group_storage = $entity_type_manager->getStorage('group');
      $microsites = $group_storage->loadMultiple();
    }
    $options = [];
    foreach ($microsites as $microsite) {
      $options[$microsite->id()] = $microsite->get('label')->value;
    }

    $form['allowed_groups'] = [
      '#type' => 'checkboxes',
      '#title' => t('Microsites with access to content type'),
      '#options' => $options,
      '#default_value' => $this->config('ecc_waste.settings')->get('allowed_groups'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('osdata_api_key'))) {
      $form_state->setErrorByName('example', $this->t('You need an API key for postcode lookup to work.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ecc_waste.settings')
      ->set('osdata_api_key', $form_state->getValue('osdata_api_key'))
      ->set('allowed_groups', $form_state->getValue('allowed_groups'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
