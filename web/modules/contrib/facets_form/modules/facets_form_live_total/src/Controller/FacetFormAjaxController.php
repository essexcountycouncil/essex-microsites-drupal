<?php

declare(strict_types = 1);

namespace Drupal\facets_form_live_total\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a controller to get total results via AJAX.
 */
class FacetFormAjaxController extends ControllerBase {

  /**
   * The facets source manager.
   *
   * @var \Drupal\facets\FacetSource\FacetSourcePluginManager
   */
  protected $facetsSourceManager;

  /**
   * The facets source manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetsManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\ResettableStackedRouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new controller instance.
   *
   * @param \Drupal\facets\FacetSource\FacetSourcePluginManager $facetsSourceManager
   *   The facets source manager.
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facetsManager
   *   The facets manager.
   * @param \Symfony\Component\HttpFoundation\Request $current_request
   *   The current request.
   * @param \Drupal\Core\Routing\ResettableStackedRouteMatchInterface $current_route_match
   *   The current route match service.
   */
  public function __construct(FacetSourcePluginManager $facetsSourceManager, DefaultFacetManager $facetsManager, Request $current_request, ResettableStackedRouteMatchInterface $current_route_match) {
    $this->facetsSourceManager = $facetsSourceManager;
    $this->facetsManager = $facetsManager;
    $this->currentRequest = $current_request;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.facets.facet_source'),
      $container->get('facets.manager'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('current_route_match')
    );
  }

  /**
   * Returns a replace command for search total or empty response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function getLiveTotal(Request $request): AjaxResponse {
    $response = new AjaxResponse();

    $facets_source_id = $request->query->get('facets_source');
    $facets = $this->facetsManager->getFacetsByFacetSourceId($facets_source_id);

    if (!$facets) {
      return $response;
    }

    /** @var \Drupal\facets\FacetSource\FacetSourcePluginInterface $facet_source */
    $facet_source = $this->facetsSourceManager->createInstance($facets_source_id);

    $facet_source->fillFacetsWithResults($facets);

    return $response->addCommand(
      new ReplaceCommand('.source-summary-count', [
        '#theme' => 'facets_summary_count',
        '#count' => $facet_source->getCount(),
      ])
    );
  }

  /**
   * Checks the access to the Ajax route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function access(): AccessResultInterface {
    $facets_source_id = $this->currentRequest->query->get('facets_source');
    if (!$facets_source_id || !$this->facetsSourceManager->hasDefinition($facets_source_id)) {
      return AccessResult::forbidden('Invalid facets_source');
    }

    [$base_plugin_id] = explode(':', $facets_source_id, 2);

    if ($base_plugin_id !== 'search_api') {
      throw new \Exception("Live total feature only supports Search API facets source.");
    }

    /** @var \Drupal\facets\FacetSource\SearchApiFacetSourceInterface $facet_source */
    $facet_source = $this->facetsSourceManager->createInstance($facets_source_id);
    $display = $facet_source->getViewsDisplay();
    if (!$display) {
      throw new \Exception("Live total feature only supports Search API facets source with Views display.");
    }

    // @see \Drupal\search_api\Plugin\search_api\display\ViewsDisplayBase::isRenderedInCurrentRequest()
    $this->currentRouteMatch->getParameters()->set('view_id', $display->storage->id());
    $this->currentRouteMatch->getParameters()->set('display_id', $display->current_display);

    return AccessResult::allowedIf($facet_source->isRenderedInCurrentRequest());
  }

}
