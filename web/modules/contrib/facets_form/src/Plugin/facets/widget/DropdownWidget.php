<?php

declare(strict_types = 1);

namespace Drupal\facets_form\Plugin\facets\widget;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\widget\ArrayWidget;
use Drupal\facets_form\FacetsFormWidgetInterface;
use Drupal\facets_form\FacetsFormWidgetTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form element alternative to 'dropdown' widget.
 *
 * @FacetsWidget(
 *   id = "facets_form_dropdown",
 *   label = @Translation("Dropdown (inside form)"),
 *   description = @Translation("A configurable widget that shows a dropdown as a form element."),
 * )
 */
class DropdownWidget extends ArrayWidget implements FacetsFormWidgetInterface, ContainerFactoryPluginInterface {

  use FacetsFormWidgetTrait;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new facet plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer')
    );
  }

  /**
   * Show the amount of results next to the result.
   *
   * @var bool
   */
  protected $showNumbers;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'default_option_label' => 'Choose',
      'child_items_prefix' => '-',
      'disabled_on_empty' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet): array {
    return [
      'default_option_label' => [
        '#type' => 'textfield',
        '#title' => $this->t('Default option label'),
        '#default_value' => $this->getConfiguration()['default_option_label'],
      ],
      'child_items_prefix' => [
        '#type' => 'textfield',
        '#title' => $this->t('Child prefix character'),
        '#description' => $this->t('A prefix to be displayed for each child. Note, that the prefix will be displayed multiple times depending on the nesting level.'),
        '#default_value' => $this->getConfiguration()['child_items_prefix'],
        '#maxlength' => 1,
      ],
      'disabled_on_empty' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Disable when there are no results. Enabling this will return the widget along with whatever is set in the "Empty facet behavior".'),
        '#default_value' => $this->getConfiguration()['disabled_on_empty'],
      ],
    ] + parent::buildConfigurationForm($form, $form_state, $facet);
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $items = parent::build($facet)[$facet->getFieldIdentifier()] ?? [];
    $this->processItems($items, $facet);

    // Honour the "Empty facet behavior", the widget's build still gets printed
    // in the page even if the empty behaviour kicks in, so we need to empty the
    // build if there are no results.
    if (empty($items) && !$this->getConfiguration()['disabled_on_empty']) {
      return [];
    }

    $options = $ancestors = [];
    if ($facet->getShowOnlyOneResult()) {
      $options[NULL] = $this->getConfiguration()['default_option_label'];
    }
    foreach ($this->processedItems as $value => $data) {
      $options[$value] = $data['label'];
      $ancestors[$value] = $data['ancestors'] ?? [];
    }

    return [
      $facet->id() => [
        '#type' => 'select',
        '#title' => $facet->getName(),
        '#title_display' => $facet->get('show_title') ? 'before' : 'invisible',
        '#options' => $options,
        '#default_value' => array_keys(array_filter($this->processedItems, function (array $item): bool {
          return $item['default'];
        })),
        '#multiple' => !$facet->getShowOnlyOneResult(),
        '#disabled' => $this->getConfiguration()['disabled_on_empty'] && empty($items),
        '#attributes' => [
          'data-drupal-facets-form-ancestors' => Json::encode($ancestors),
        ],
      ],
    ];
  }

}
