<?php

declare(strict_types = 1);

namespace Drupal\facets_form_date_range\Plugin\facets\widget;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\widget\ArrayWidget;
use Drupal\facets\Result\Result;
use Drupal\facets_form\FacetsFormWidgetInterface;
use Drupal\facets_form\FacetsFormWidgetTrait;
use Drupal\facets_form_date_range\DateRange;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Date range widget as form element.
 *
 * @FacetsWidget(
 *   id = "facets_form_date_range",
 *   label = @Translation("Date range (inside form)"),
 *   description = @Translation("A configurable widget that shows a date range as a form element."),
 * )
 */
class DateRangeWidget extends ArrayWidget implements FacetsFormWidgetInterface, ContainerFactoryPluginInterface {

  use FacetsFormWidgetTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The date/time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new facet plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The date/time service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'date_type' => DateRange::TYPE_DATE,
      'label' => [
        'from' => $this->t('From'),
        'to' => $this->t('To'),
      ],
      'date_format' => [
        'type' => 'medium',
        'custom' => NULL,
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet): array {
    $configuration = $this->getConfiguration();
    $date_formats = [];
    $format_storage = $this->entityTypeManager->getStorage('date_format');
    foreach ($format_storage->loadMultiple() as $id => $value) {
      $date_formats[$id] = $this->t('@name format: @date', [
        '@name' => $value->label(),
        '@date' => $this->dateFormatter->format($this->time->getRequestTime(), $id),
      ]);
    }

    $date_formats['custom'] = $this->t('Custom');

    return [
      'date_type' => [
        '#type' => 'select',
        '#title' => $this->t('Date type'),
        '#options' => [
          DateRange::TYPE_DATE => $this->t('Date only'),
          DateRange::TYPE_DATETIME => $this->t('Date and time'),
        ],
        '#default_value' => $configuration['date_type'],
      ],
      'label' => [
        'from' => [
          '#type' => 'textfield',
          '#title' => $this->t('Start date label'),
          '#default_value' => $configuration['label']['from'],
        ],
        'to' => [
          '#type' => 'textfield',
          '#title' => $this->t('End date label'),
          '#default_value' => $configuration['label']['to'],
        ],
      ],
      'date_format' => [
        '#type' => 'details',
        '#title' => $this->t('Summary date format'),
        'type' => [
          '#type' => 'select',
          '#title' => $this->t('Format'),
          '#options' => $date_formats,
          '#default_value' => $configuration['date_format']['type'],
        ],
        'custom' => [
          '#type' => 'textfield',
          '#title' => $this->t('Custom format'),
          '#description' => $this->t('See <a href="https://www.php.net/manual/datetime.format.php#refsect1-datetime.format-parameters" target="_blank">the documentation for PHP date formats</a>.'),
          '#default_value' => $configuration['date_format']['custom'],
          '#states' => [
            'visible' => [
              ':input[name="widget_config[date_format][type]"]' => [
                'value' => 'custom',
              ],
            ],
          ],
        ],
      ],
    ] + parent::buildConfigurationForm($form, $form_state, $facet);
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    if (!$this->checkDependentProcessors($facet)) {
      return [];
    }

    $date_range = DateRange::createFromFacet($facet);
    $configuration = $facet->getWidgetInstance()->getConfiguration();
    $date_time_element = $configuration['date_type'] === DateRange::TYPE_DATE ? 'none' : 'time';

    if (!$facet->getResults()) {
      // Set fake results so that the build will avoid the empty behaviour.
      // @see \Drupal\facets\FacetManager\DefaultFacetManager::build()
      $facet->setResults([new Result($facet, NULL, NULL, 0)]);
    }

    return [
      $facet->id() => [
        '#type' => 'fieldset',
        '#tree' => TRUE,
        '#title' => $facet->get('show_title') ? $facet->getName() : NULL,
        'from' => [
          '#type' => 'datetime',
          '#title' => $configuration['label']['from'],
          '#date_date_element' => 'date',
          '#date_time_element' => $date_time_element,
          '#default_value' => $date_range->getFrom(),
          '#attributes' => [
            'data-timezone' => $date_range->getFromTimezone(),
          ],
        ],
        'to' => [
          '#type' => 'datetime',
          '#title' => $configuration['label']['to'],
          '#date_date_element' => 'date',
          '#date_time_element' => $date_time_element,
          '#date_time_format' => 'H:i:sP',
          '#default_value' => $date_range->getTo(),
          '#attributes' => [
            'data-timezone' => $date_range->getToTimezone(),
          ],
        ],
        '#cache' => [
          'contexts' => [
            'url.query_args',
            'url.path',
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareValueForUrl(FacetInterface $facet, array &$form, FormStateInterface $form_state): array {
    $date_range = (new DateRange())
      ->setType($this->getConfiguration()['date_type'])
      ->setFrom($form_state->getValue([$facet->id(), 'from']))
      ->setTo($form_state->getValue([$facet->id(), 'to']));
    return !$date_range->isEmpty() ? [(string) $date_range] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType(): string {
    return 'facets_form_date_range';
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptionLabel(array $item, int $depth, FacetInterface $facet) {
  }

  /**
   * Helper function to see if a Facet should be enabled based on its dependencies.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet we should build.
   *
   * @return bool
   *   True if Facet should be enabled.
   */
  protected function checkDependentProcessors(FacetInterface $facet): bool {
    $processors = $facet->getProcessors();

    // In case dependent processor is not enabled, facet should be enabled by default.
    if (!isset($processors['dependent_processor'])) {
      return TRUE;
    }

    $facet_manager = \Drupal::service('facets.manager');
    $facets = $facet_manager->getFacetsByFacetSourceId($facet->getFacetSourceId());
    $conditions = $processors['dependent_processor']->getConfiguration();

    // Load enabled conditions to be checked.
    $enabled_conditions = [];
    foreach ($conditions as $facet_id => $condition) {
      if (empty($condition['enable'])) {
        continue;
      }
      $enabled_conditions[$facet_id] = $condition;
    }

    // Process conditions to see if facet should be enabled.
    foreach ($enabled_conditions as $facet_id => $condition_settings) {
      // If any condition is not met, facet will be disabled.
      if (!isset($facets[$facet_id]) || !$processors['dependent_processor']->isConditionMet($condition_settings, $facets[$facet_id])) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
