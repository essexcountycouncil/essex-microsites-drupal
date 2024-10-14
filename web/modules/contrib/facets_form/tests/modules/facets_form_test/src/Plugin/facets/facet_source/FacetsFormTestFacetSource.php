<?php

declare(strict_types = 1);

namespace Drupal\facets_form_test\Plugin\facets\facet_source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\facets\FacetSource\FacetSourcePluginBase;

/**
 * Testing faces source plugin.
 *
 * @FacetsFacetSource(
 *   id = "facets_form_test",
 *   display_id =  "foo",
 *   label = "Facets Form Test",
 * )
 */
class FacetsFormTestFacetSource extends FacetSourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function fillFacetsWithResults(array $facets): void {}

  /**
   * {@inheritdoc}
   */
  public function getPath(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDataDefinition($field_name): DataDefinitionInterface {
    return DataDefinition::create('string');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    return [];
  }

}
