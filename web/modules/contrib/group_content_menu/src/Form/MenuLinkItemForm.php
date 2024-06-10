<?php

declare(strict_types=1);

namespace Drupal\group_content_menu\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\menu_link_content\Form\MenuLinkContentForm;

/**
 * Form wrapper for MenuLinkContentForm.
 *
 * @ingroup group_content_menu
 */
final class MenuLinkItemForm extends MenuLinkContentForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirect('<none>');

    $group = $this->getRouteMatch()->getParameter('group');
    $group_content_menu = $this->getRouteMatch()->getParameter('group_content_menu');
    if ($group_content_menu !== NULL && $group !== NULL) {
      $form_state->setRedirect('entity.group_content_menu.edit_form', [
        'group' => $group->id(),
        'group_content_menu' => $group_content_menu->id(),
      ]);
    }
  }

}
