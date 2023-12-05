<?php

declare(strict_types=1);

namespace Drupal\group_content_menu\Form;

use Drupal\Core\Url;
use Drupal\menu_link_content\Form\MenuLinkContentDeleteForm;

/**
 * Form wrapper for MenuLinkContentDeleteForm.
 *
 * @ingroup group_content_menu
 */
final class MenuLinkItemDeleteForm extends MenuLinkContentDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    $group = $this->getRouteMatch()->getParameter('group');
    $group_content_menu = $this->getRouteMatch()->getParameter('group_content_menu');
    if ($group_content_menu !== NULL && $group !== NULL) {
      return new Url('entity.group_content_menu.edit_form', [
        'group' => $group->id(),
        'group_content_menu' => $group_content_menu->id(),
      ]);
    }
    return new Url('<front>');
  }

}
