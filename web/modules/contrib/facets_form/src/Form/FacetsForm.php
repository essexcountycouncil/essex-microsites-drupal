<?php

declare(strict_types = 1);

namespace Drupal\facets_form\Form;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\facets\Utility\FacetsUrlGenerator;
use Drupal\facets_form\Event\TriggerWidgetChangeJavaScriptEvent;
use Drupal\facets_form\FacetsFormWidgetInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Exposes the facets as a form.
 */
class FacetsForm extends FormBase {

  /**
   * The facets manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetsManager;

  /**
   * The facets url generator.
   *
   * @var \Drupal\facets\Utility\FacetsUrlGenerator
   */
  protected $facetsUrlGenerator;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * Constructs a new form instance.
   *
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facets_manager
   *   The facets manager.
   * @param \Drupal\facets\Utility\FacetsUrlGenerator $facets_url_generator
   *   The facets url generator.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   */
  public function __construct(DefaultFacetManager $facets_manager, FacetsUrlGenerator $facets_url_generator, EventDispatcherInterface $event_dispatcher, LibraryDiscoveryInterface $library_discovery) {
    $this->facetsManager = $facets_manager;
    $this->facetsUrlGenerator = $facets_url_generator;
    $this->eventDispatcher = $event_dispatcher;
    $this->libraryDiscovery = $library_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('facets.manager'),
      $container->get('facets.utility.url_generator'),
      $container->get('event_dispatcher'),
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $source_id = NULL, array $config = NULL): array {
    if (!$source_id) {
      return [];
    }

    // @todo Set proper cache metadata.
    $cache = new CacheableMetadata();

    $facets = $this->facetsManager->getFacetsByFacetSourceId($source_id);
    if (!$facets) {
      $cache->applyTo($form);
      return $form;
    }

    // Sort facets by weight.
    uasort($facets, function (FacetInterface $a, FacetInterface $b) {
      return $a->getWeight() <=> $b->getWeight();
    });

    // Ask 3rd-party if they want to enable Javascript capability.
    $event = new TriggerWidgetChangeJavaScriptEvent($source_id, $config);
    $this->eventDispatcher->dispatch($event);
    if ($trigger_widget_change_event = $event->shouldTriggerWidgetChangeEvent()) {
      $libraries = $this->libraryDiscovery->getLibrariesByExtension('facets_form');
    }

    $has_plugin_library = FALSE;
    foreach ($facets as $facet) {
      $widget = $facet->getWidgetInstance();
      if ($widget instanceof FacetsFormWidgetInterface && (empty($config['facets']) || in_array($facet->id(), $config['facets']))) {
        $cache->addCacheableDependency($facet);
        $build = $this->facetsManager->build($facet);
        $build[0][$facet->id()]['#attributes']['data-drupal-facets-form-widget'] = $widget->getPluginId();
        $build[0][$facet->id()]['#attributes']['data-drupal-facets-form-facet'] = $facet->id();
        if ($trigger_widget_change_event) {
          $library = "plugin.{$widget->getPluginId()}";
          if (isset($libraries[$library])) {
            $build['#attached']['library'][] = "facets_form/{$library}";
            $has_plugin_library = TRUE;
          }
        }
        $form['facets'][$facet->id()] = $build;
      }
    }

    if ($has_plugin_library) {
      $form['facets']['#attached']['library'][] = "facets_form/plugin_base";
    }

    if (!isset($form['facets'])) {
      $cache->applyTo($form);
      return $form;
    }

    $form_state->set('source_id', $source_id);
    $form_state->set('block_settings', $config);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $config['button']['label']['submit'],
    ];

    $form['actions']['reset'] = [
      '#type' => 'link',
      '#title' => $config['button']['label']['reset'],
      '#attributes' => [
        'class' => ['button'],
      ],
      '#url' => $this->buildRedirectUrl(TRUE),
    ];

    // Mark this form as facets form.
    $form['#attributes']['data-drupal-facets-form'] = $source_id;

    // Add the cache contexts.
    $cache->setCacheContexts(['url.query_args']);
    $cache->applyTo($form);


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $active_filters = [];
    foreach ($this->facetsManager->getFacetsByFacetSourceId($form_state->get('source_id')) as $facet) {
      $widget = $facet->getWidgetInstance();
      if ($widget instanceof FacetsFormWidgetInterface) {
        $active_filters[$facet->id()] = $widget->prepareValueForUrl($facet, $form, $form_state);
      }
    }

    $active_filters = array_filter($active_filters);
    if ($active_filters) {
      $url = $this->facetsUrlGenerator->getUrl($active_filters, FALSE);
      $form_state->setRedirectUrl($url);
      return;
    }

    // If there are no active filters, we redirect to the current URL without
    // any filters in the URL but still preserving non-filter query parameters.
    $form_state->setRedirectUrl($this->buildRedirectUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'facets_form';
  }

  /**
   * Builds the redirect URL and optionally excludes the filters from query.
   *
   * Used to build the URL either when resetting the filters or when the form is
   * submitted with no active filters.
   *
   * @param bool $exclude_filters
   *   (optional) Whether to exclude the facets filters. Defaults to FALSE.
   *
   * @return \Drupal\Core\Url
   *   An URL object.
   */
  protected function buildRedirectUrl(bool $exclude_filters = FALSE): Url {
    $query = $this->getRequest()->query->all();
    if ($exclude_filters) {
      // @todo For now we're only providing support for the `query_string`
      //   facets URL processor.
      unset($query['f']);
    }
    return Url::fromRoute('<current>', [], ['query' => $query]);
  }

}
