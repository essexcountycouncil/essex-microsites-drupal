<?php

declare(strict_types = 1);

namespace Drupal\facets_form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Widget\WidgetPluginInterface;

/**
 * Provides an interface for widget plugins eligible for facets forms.
 */
interface FacetsFormWidgetInterface extends WidgetPluginInterface {

  /**
   * Prepares the values to be passed to the URL generator from the submission.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The active filters to be handled by the URL generator.
   */
  public function prepareValueForUrl(FacetInterface $facet, array &$form, FormStateInterface $form_state): array;

}
