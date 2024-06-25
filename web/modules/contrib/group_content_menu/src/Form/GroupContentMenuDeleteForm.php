<?php

namespace Drupal\group_content_menu\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Form controller for Group menu instance edit forms.
 *
 * @ingroup group_content_menu
 */
class GroupContentMenuDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    $group = \Drupal::routeMatch()->getParameter('group');
    if ($group) {
      return Url::fromRoute('entity.group_content_menu.collection', [
        'group' => $group->id(),
      ]);
    }
    return Url::fromRoute('<front>');
  }

}
