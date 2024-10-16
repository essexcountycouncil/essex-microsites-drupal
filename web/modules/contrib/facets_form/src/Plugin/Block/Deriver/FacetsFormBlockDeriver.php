<?php

declare(strict_types = 1);

namespace Drupal\facets_form\Plugin\Block\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives the 'facets_form' block plugin by facets sources.
 */
class FacetsFormBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The facets source plugin manager service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $facetsSourcePluginManager;

  /**
   * Constructs a new deriver instance.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $facets_source_plugin_manager
   *   The facets source plugin manager service.
   */
  public function __construct(PluginManagerInterface $facets_source_plugin_manager) {
    $this->facetsSourcePluginManager = $facets_source_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id): self {
    return new static($container->get('plugin.manager.facets.facet_source'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    foreach ($this->facetsSourcePluginManager->getDefinitions() as $plugin_id => $definition) {
      $this->derivatives[$plugin_id] = [
        'admin_label' => $this->t('Facet form: @source', [
          '@source' => strip_tags((string) $definition['label']),
        ]),
      ] + $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
