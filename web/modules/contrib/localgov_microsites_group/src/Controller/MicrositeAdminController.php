<?php

namespace Drupal\localgov_microsites_group\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Redirect to current group admin page.
 */
class MicrositeAdminController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('context.repository')
    );
  }

  /**
   * Initialise a MicrositeAdminController instance.
   *
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   The context repository service.
   */
  public function __construct(protected ContextRepositoryInterface $contextRepository) {
  }

  /**
   * Redirect to microsite admin page /group/{group ID}.
   */
  public function redirectToMicrositeAdmin() {

    $group_id = $this->getGroupId();
    $url = Url::fromRoute('entity.group.canonical', ['group' => $group_id]);
    return new RedirectResponse($url->toString());
  }

  /**
   * Access check to microsite admin page redirect.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {

    if (!is_null($this->getGroupId())) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * Tries to retrieve the current group id from the current context.
   *
   * @return int|null
   *   The group id or null.
   */
  protected function getGroupId(): int|null {
    $context_id = $this->config('group_sites.settings')->get('context_provider');
    if ($context_id === NULL) {
      return NULL;
    }

    $contexts = $this->contextRepository->getRuntimeContexts([$context_id]);

    $context = count($contexts) ? reset($contexts) : NULL;

    if ($group = $context?->getContextValue()) {
      if (!$group instanceof GroupInterface) {
        throw new \InvalidArgumentException('Context value is not a Group entity.');
      }
      return $group->id();
    }
    return NULL;
  }

}
