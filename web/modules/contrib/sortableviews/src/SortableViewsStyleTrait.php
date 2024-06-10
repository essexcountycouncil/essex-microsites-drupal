<?php

namespace Drupal\sortableviews;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides sortable functionality for View styles.
 */
trait SortableViewsStyleTrait {

  /**
   * An instance of the entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * An URL generator service instance.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * An instance of the Entity Field Manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The Entity Field Manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager, UrlGeneratorInterface $url_generator, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityManager = $entity_manager;
    $this->urlGenerator = $url_generator;
    $this->entityFieldManager = $entity_field_manager;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('url_generator'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Returns Sizzle/CSS compatible selector.
   *
   * The selector to the container of sortable elements starting
   * from .view-content.
   *
   * @return string
   *   The selector (any string) or literally "self" when the
   *   actual container is .view-content itself.
   */
  protected function javascriptSelector() {
    return 'self';
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['weight_field'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();
    $this->view->get_total_rows = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $original_order = [];
    foreach ($this->view->result as $result) {
      $original_order[] = $result->_entity->id();
    }
    $build['#attached']['drupalSettings']['sortableviews'][$this->view->dom_id] = [
      'original_order' => $original_order,
      'view_name' => $this->view->storage->id(),
      'display_name' => $this->view->current_display,
      'ajax_url' => $this->urlGenerator->generateFromRoute('sortableviews.ajax'),
      'dom_id' => $this->view->dom_id,
      'selector' => $this->javascriptSelector(),
      'sort_order' => $this->retrieveSortOrder(),
      // The following items allows to skip rebuilding the view.
      'page_number' => isset($this->view->pager->current_page) ? (int) $this->view->pager->current_page : 0,
      'total_rows' => isset($this->view->total_rows) ? $this->view->total_rows : count($this->view->result),
      'items_per_page' => isset($this->view->pager->options['items_per_page']) ? $this->view->pager->options['items_per_page'] : NULL,
    ];
    $build['#attached']['library'][] = 'sortableviews/sortableviews.sortable';
    return $build;
  }

  /**
   * Returns the sort order as specified in display settings.
   *
   * @return string
   *   "asc" or "desc" depending on the display settings.
   */
  protected function retrieveSortOrder() {
    if (!empty($this->view->sort)) {
      foreach ($this->view->sort as $sort) {
        if (isset($sort->definition['field_name']) && $sort->definition['field_name'] == $this->options['weight_field'] && $sort->options['order'] == 'DESC') {
          return 'desc';
        }
      }
    }
    return 'asc';
  }

  /**
   * Returns list of eligible fields for storing weight.
   *
   * Eligible fields can be either fields or Base Fields
   * and must be of the integer type.
   *
   * @return array
   *   An array whose keys and values are field machine names.
   */
  protected function retrieveEligibleFieldsForWeight() {
    $field_options = [];

    // Grab all entity fields.
    $fields = $this->entityManager->getStorage('field_storage_config')->loadByProperties([
      'entity_type' => $this->view->getBaseEntityType()->id(),
      'type' => 'integer',
    ]);
    foreach ($fields as $field) {
      $field_options[$field->getName()] = $field->getName();
    }

    // Grab all entity base fields.
    $base_fields = $this->entityFieldManager->getBaseFieldDefinitions($this->view->getBaseEntityType()->id());
    foreach ($base_fields as $base_field) {
      if ($base_field->getType() == 'integer') {
        $field_options[$base_field->getName()] = $base_field->getName();
      }
    }

    // From all picked fields, we remove those that are entity keys.
    $keys = array_values($this->view->getBaseEntityType()->getKeys());
    return array_diff($field_options, $keys);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['weight_field'] = [
      '#type' => 'select',
      '#title' => t('Field to use for weight'),
      '#description' => t('Select the field you want Sortableviews to use for storing weights.'),
      '#options' => $this->retrieveEligibleFieldsForWeight(),
      '#required' => TRUE,
      '#default_value' => $this->options['weight_field'],
    ];
    parent::buildOptionsForm($form, $form_state);
  }

}
