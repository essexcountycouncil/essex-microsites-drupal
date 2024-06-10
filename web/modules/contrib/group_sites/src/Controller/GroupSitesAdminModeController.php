<?php

namespace Drupal\group_sites\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Drupal\group_sites\GroupSitesAdminModeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides group_sites admin mode route controllers.
 */
class GroupSitesAdminModeController extends ControllerBase {

  public function __construct(
    protected GroupSitesAdminModeInterface $adminMode,
    protected RequestStack $requestStack,
    RedirectDestinationInterface $redirectDestination,
  ) {
    $this->redirectDestination = $redirectDestination;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('group_sites.admin_mode'),
      $container->get('request_stack'),
      $container->get('redirect.destination')
    );
  }

  /**
   * Activates admin mode for the current user.
   */
  public function activate(): RedirectResponse {
    $this->adminMode->setAdminMode(TRUE);
    return new RedirectResponse($this->getRedirectPath());
  }

  /**
   * Deactivates admin mode for the current user.
   */
  public function deactivate(): RedirectResponse {
    $this->adminMode->setAdminMode(FALSE);
    return new RedirectResponse($this->getRedirectPath());
  }

  /**
   * Gets the path to use for the redirect response.
   *
   * This will look for the 'destination' query argument and default to <front>
   * if none was found.
   *
   * @return string
   *   The redirect path.
   */
  protected function getRedirectPath() {
    // \Drupal\Core\Routing\RedirectDestination::get() cannot be used directly
    // because it will use <current> if 'destination' is not in the query
    // string.
    return $this->requestStack->getCurrentRequest()->query->has('destination')
      ? $this->getRedirectDestination()->get()
      : Url::fromRoute('<front>')->toString();
  }

}
