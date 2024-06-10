<?php

namespace Drupal\replicate_ui\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLink;

/**
 * Provides a entity replicate link
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("replicate_ui_link")
 */
class ReplicateUILink extends EntityLink {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'replicate';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Replicate');
  }

}
