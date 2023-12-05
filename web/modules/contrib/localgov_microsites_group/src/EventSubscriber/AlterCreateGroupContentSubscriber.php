<?php

namespace Drupal\localgov_microsites_group\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Alter the create group content page.
 */
class AlterCreateGroupContentSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AlterCreateGroupContentSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Alters the controller output.
   */
  public function onView(ViewEvent $event) {
    $request = $event->getRequest();
    $route = $request->attributes->get('_route');

    // Change the group add new node content page.
    if ($route == 'entity.group_relationship.group_node_add_page') {
      $build = $event->getControllerResult();
      if (isset($build['#bundles'])) {
        // $storage = $this->entityTypeManager
        // ->getStorage('group_relationship_type');
        // Change the link label and description.
        foreach ($build['#bundles'] as $id => $bundle) {
          // $group_relationship_type = $storage->load($id);
          // $build['#bundles'][$id]['description'] =
          // $group_relationship_type->getDescription();
          $build['#bundles'][$id]['add_link']->setText($bundle['label']);
        }

        // Sort bundles alphabetically.
        usort($build['#bundles'], function ($a, $b) {
          return $a['label'] <=> $b['label'];
        });

        $event->setControllerResult($build);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::VIEW][] = ['onView', 10];
    return $events;
  }

}
