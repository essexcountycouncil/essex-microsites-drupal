<?php

declare(strict_types = 1);

namespace Drupal\facets_form\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\facets_form\FacetsFormWidgetInterface;
use Drupal\facets_form\Form\FacetsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Exposes the facets form as block.
 *
 * @Block(
 *  id = "facets_form",
 *  admin_label = @Translation("Facets form"),
 *  category = @Translation("Facets"),
 *  deriver = "Drupal\facets_form\Plugin\Block\Deriver\FacetsFormBlockDeriver",
 * )
 */
class FacetsFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The facets manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetsManager;

  /**
   * Constructs a new form instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facets_manager
   *   The facets manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder, DefaultFacetManager $facets_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
    $this->facetsManager = $facets_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('facets.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'button' => [
        'label' => [
          'submit' => $this->t('Search'),
          'reset' => $this->t('Clear filters'),
        ],
      ],
      'facets' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $facets = $this->facetsManager->getFacetsByFacetSourceId($this->getDerivativeId());
    $facets_options = [];

    foreach ($facets as $facet) {
      // Check if the facet is an instance of the widget created.
      if ($facet->getWidgetInstance() instanceof FacetsFormWidgetInterface) {
        $facets_options[$facet->id()] = $facet->getName();
      }
    }

    // Creating items if exists.
    $form['facets'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Limit to facets'),
      '#description' => $this->t('Please select the facets you need to be displayed. Please note that if none facets is selected, all facets will be displayed.'),
      '#options' => $facets_options,
      '#default_value' => $config['facets'],
    ];
    $form['submit_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submit button'),
      '#description' => $this->t('Add the text which overrides the facet submit label button.'),
      '#default_value' => $config['button']['label']['submit'],
      '#required' => TRUE,
    ];
    $form['reset_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reset button'),
      '#description' => $this->t('Add the text which overrides the facet reset label button.'),
      '#default_value' => $config['button']['label']['reset'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);
    $this->setConfigurationValue('button', [
      'label' => [
        'submit' => $form_state->getValue('submit_label'),
        'reset' => $form_state->getValue('reset_label'),
      ],
    ]);
    $this->setConfigurationValue('facets', array_keys(array_filter($form_state->getValue('facets'))));
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      'form' => $this->formBuilder->getForm(FacetsForm::class, $this->getDerivativeId(), $this->getConfiguration()),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $dependencies = [];
    $facets = $this->facetsManager->getFacetsByFacetSourceId($this->getDerivativeId());
    $config = $this->getConfiguration();
    foreach ($facets as $facet) {
      // Check if the facet is an instance of the widget created.
      if ($facet->getWidgetInstance() instanceof FacetsFormWidgetInterface) {
        if (empty($config['facets']) || in_array($facet->id(), $config['facets'])) {
          $dependencies[$facet->getConfigDependencyKey()][] = $facet->getConfigDependencyName();
        }
      }
    }

    return $dependencies;
  }

}
