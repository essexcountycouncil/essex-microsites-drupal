<?php

namespace Drupal\domain_group\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\domain_group\DomainGroupHelper;
use Drupal\domain_group\DomainGroupResolverInterface;
use Drupal\entity\QueryAccess\QueryAccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Query access event subscriber.
 *
 * This can handle `group_relationship` queries.
 * For `group` queries adding the `id` base field is not reliable, so this is
 * done in EntityQueryAlter.
 * For all entities referenced by group_relationship in a group the
 * group_relationship_field_data table needs to be joined if there is a plugin
 * active for that content type. The logic to add this, if it's not already
 * there is also in EntityQueryAlter.
 *
 * @see \Drupal\domain_group\QueryAccess\EntityQueryAlter
 */
class QueryAccessSubscriber implements EventSubscriberInterface {

  /**
   * Domain group resolver service.
   *
   * @var \Drupal\domain_group\DomainGroupResolverInterface
   */
  protected $domainGroupResolver;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\domain_group\DomainGroupResolverInterface $domain_group_resolver
   *   Domain group resolver service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(DomainGroupResolverInterface $domain_group_resolver, MessengerInterface $messenger) {
    $this->domainGroupResolver = $domain_group_resolver;
    $this->messenger = $messenger;
  }

  /**
   * Response event handler for group content queries.
   *
   * @param \Drupal\entity\QueryAccess\QueryAccessEvent $event
   *   Query access event.
   */
  public function groupContentQueryAccessAlter(QueryAccessEvent $event) {
    $conditions = $event->getConditions();
    $domain_group_config = \Drupal::config('domain_group.settings');
    $conditions->addCacheableDependency($domain_group_config);
    if (!$domain_group_config->get('unique_group_access')) {
      return;
    }

    $active_id = $this->domainGroupResolver->getActiveDomainGroupId();
    if (!empty($active_id)) {
      $conditions->addCondition('gid', $active_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'entity.query_access.group_relationship' => 'groupContentQueryAccessAlter',
    ];
  }

}
