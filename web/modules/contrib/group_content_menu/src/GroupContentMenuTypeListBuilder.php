<?php

namespace Drupal\group_content_menu;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of group content menu type entities.
 *
 * @see \Drupal\group_content_menu\Entity\GroupContentMenuType
 */
class GroupContentMenuTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Label');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No group menu types available. <a href=":link">Add group menu type</a>.',
      [':link' => Url::fromRoute('entity.group_content_menu_type.add_form')->toString()]
    );

    return $build;
  }

}
