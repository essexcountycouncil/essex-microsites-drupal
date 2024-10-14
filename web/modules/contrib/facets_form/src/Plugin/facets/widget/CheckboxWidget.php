<?php

declare(strict_types = 1);

namespace Drupal\facets_form\Plugin\facets\widget;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\widget\ArrayWidget;
use Drupal\facets_form\FacetsFormWidgetInterface;
use Drupal\facets_form\FacetsFormWidgetTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form element alternative to 'checkbox' widget.
 *
 * @FacetsWidget(
 *   id = "facets_form_checkbox",
 *   label = @Translation("Checkboxes (inside form)"),
 *   description = @Translation("A configurable widget that shows checkboxes as a form element."),
 * )
 */
class CheckboxWidget extends ArrayWidget implements FacetsFormWidgetInterface, ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'disabled_on_empty' => FALSE,
      'indent_class' => 'indented',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet): array {
    return [
      'disabled_on_empty' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Disable when there are no results'),
        '#default_value' => $this->getConfiguration()['disabled_on_empty'],
      ],
      'indent_class' => [
        '#type' => 'textfield',
        '#title' => $this->t('Class used for indentation'),
        '#default_value' => $this->getConfiguration()['indent_class'],
      ],
    ] + parent::buildConfigurationForm($form, $form_state, $facet);
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet): array {
    $items = parent::build($facet)[$facet->getFieldIdentifier()] ?? [];

    $this->processItems($items, $facet);

    $options = $default_value = $depths = $ancestors = [];
    foreach ($this->processedItems as $value => $data) {
      $options[$value] = $data['label'];
      if ($data['default']) {
        $default_value[] = $value;
      }
      $depths[$value] = $data['depth'];
      $ancestors[$value] = $data['ancestors'] ?? [];
    };

    return [
      $facet->id() => [
        '#type' => 'fieldset',
        '#title' => $facet->get('show_title') ? $facet->getName() : NULL,
        '#access' => !empty($this->processedItems) && !$facet->getOnlyVisibleWhenFacetSourceIsVisible(),
        '#attributes' => [
          'data-drupal-facets-form-ancestors' => Json::encode($ancestors),
        ],
        $facet->id() => [
          // @todo Honour 'Ensure that only one result can be displayed' config.
          // @see https://www.drupal.org/project/facets_form/issues/3227076
          '#type' => 'checkboxes',
          // @todo This is not working. Open a followup.
          '#disabled' => $this->getConfiguration()['disabled_on_empty'] && empty($items),
          '#options' => $options,
          '#default_value' => $default_value,
          '#after_build' => [[static::class, 'indentCheckboxes']],
          '#depths' => $depths,
          '#ancestors' => $ancestors,
          '#indent_class' => $this->getConfiguration()['indent_class'],
        ],
      ],
    ];
  }

  /**
   * Wraps each checkbox in indent containers, depending on their depth.
   *
   * @param array $element
   *   The 'checkboxes' element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   *
   * @return array
   *   The element.
   */
  public static function indentCheckboxes(array $element, FormStateInterface $form_state): array {
    foreach (Element::children($element) as $value) {
      $element[$value]['#prefix'] = str_repeat('<div class="' . $element['#indent_class'] . '">', $element['#depths'][$value]);
      $element[$value]['#suffix'] = str_repeat('</div>', $element['#depths'][$value]);
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareValueForUrl(FacetInterface $facet, array &$form, FormStateInterface $form_state): array {
    return array_keys(array_filter($form_state->getValue($facet->id(), [])));
  }

}
