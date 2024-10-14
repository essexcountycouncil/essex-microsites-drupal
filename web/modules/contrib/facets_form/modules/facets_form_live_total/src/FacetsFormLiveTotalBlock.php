<?php

declare(strict_types=1);

namespace Drupal\facets_form_live_total;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets_form\Plugin\Block\FacetsFormBlock;

/**
 * Extends FacetsFormBlock to provide live total support.
 *
 * @see \Drupal\facets_form\Plugin\Block\FacetsFormBlock
 */
class FacetsFormLiveTotalBlock extends FacetsFormBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'live_total' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $form['live_total'] = [
      '#type' => 'checkbox',
      '#title' => t('Live total'),
      '#description' => t("If checked, the total amount of results is updated live, as the user is interacting with the form."),
      '#default_value' => $this->getConfiguration()['live_total'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);
    // Some browsers are returning the checkbox value as integer. We need to
    // explicitly cast it to a boolean so that we honour the config schema.
    $this->setConfigurationValue('live_total', (bool) $form_state->getValue('live_total'));
  }

}
