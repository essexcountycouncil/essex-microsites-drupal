<?php

namespace Drupal\sortableviews\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for sortableviews ajax calls.
 */
class AjaxController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * An instance of the entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Builds a new AjaxController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns the entity order adjusted for the view pager.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   An array with the adjusted sort order where the key
   *   is the entity weight and the value, the entity id.
   */
  protected function retrieveOrderFromRequest(Request $request) {
    // Adjust order for pager in asc fashion.
    if ($request->get('items_per_page') && $request->get('sort_order') == 'asc') {
      $adjusted_order = [];
      foreach ($request->get('current_order') as $index => $value) {
        $new_index = $index + ($request->get('page_number') * $request->get('items_per_page'));
        $adjusted_order[$new_index] = $value;
      }
      return $adjusted_order;
    }
    // Adjust order for pager in desc fashion.
    if ($request->get('items_per_page') && $request->get('sort_order') == 'desc') {
      $complete_pages = (int) ($request->get('total_rows') / $request->get('items_per_page'));
      if ($complete_pages == $request->get('page_number')) {
        // This is the last page of the view.
        return $request->get('current_order');
      }
      $adjusted_order = [];
      $mod = (int) $request->get('total_rows') % $request->get('items_per_page');
      foreach ($request->get('current_order') as $index => $value) {
        $new_index = $index + ($complete_pages - ($request->get('page_number') + 1)) * $request->get('items_per_page') + $mod;
        $adjusted_order[$new_index] = $value;
      }
      return $adjusted_order;
    }
    return $request->get('current_order');
  }

  /**
   * Saves new weights.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An response with Ajax commands.
   */
  public function ajaxSave(Request $request) {
    $entity_type = $request->get('entity_type');
    $field = $request->get('weight_field');
    $current_order = $this->retrieveOrderFromRequest($request);
    $entities = $this->entityManager->getStorage($entity_type)->loadMultiple(array_values($current_order));

    foreach ($entities as $entity) {
      $entity->set($field, array_search($entity->id(), $current_order));
      $entity->save();
    }

    $response = new AjaxResponse();
    $response->addCommand(new MessageCommand($this->t('Changes have been saved.')));
    $response->addCommand(new RemoveCommand('.js-view-dom-id-' . $request->get('dom_id') . ' .sortableviews-ajax-trigger'));
    return $response;
  }

}
