<?php

namespace Drupal\ecc_waste\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\StringFilter;
use Drupal\views\Views;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Custom Views filter to make cURL request and filter based on taxonomy term.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("postcode_filter")
 */
class PostcodeFilter extends FilterPluginBase {

  /**
   * The current display.
   *
   * @var string
   *   The current display of the view.
   */
  protected $currentDisplay;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Filter by postcode');
    $this->definition['options callback'] = [$this, 'generateOptions'];
    $this->currentDisplay = $view->current_display;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $tid = $this->getTermIdFromPostcode($this->value[0]);

    $field = 'node__field_disposal_option_districts.field_disposal_option_districts_target_id';
    // Implement the logic to make cURL request and apply taxonomy term filter.
    // Use $this->value to get the value from the submitted form.
    // Modify $this->query->addWhere() to apply the taxonomy term filter.
    $this->ensureMyTable();

    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;
    $table = array_key_first($query->tables);
    $this->query->addTable('node__field_disposal_option_districts');
    $this->query->addWhere($this->options['group'], $field, $tid, '=');
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['postcode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postcode'),
      '#description' => $this->t('Enter your postcode.'),
      '#required' => FALSE,
    ];
    if ($this->canExpose()) {
      $this->showExposeButton($form, $form_state);
      $form['expose']['identifier'] = [
        '#type' => 'textfield',
        '#default_value' => $this->options['expose']['identifier'],
        '#title' => $this
          ->t('Filter identifier'),
        '#size' => 40,
        '#description' => $this
          ->t('This will appear in the URL after the ? to identify this filter. Cannot be blank. Only letters, digits and the dot ("."), hyphen ("-"), underscore ("_"), and tilde ("~") characters are allowed.'),
      ];
    }
  }


  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postcode'),
      '#description' => $this->t('Enter your postcode.'),
      '#required' => FALSE,
    ];
  }

  /**
   * Determine if a filter can be exposed.
   */
  public function canExpose() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['postcode'] = ['default' => NULL];

    return $options;
  }

  /**
   * Helper function to poll 3rd party datasource for postcode
   * and get a taxonomy ID.
   */
  public function getTermIdFromPostcode($value) {
    if (!empty($value)) {
      $url = 'https://api.os.uk/search/places/v1/postcode?postcode=' . $value . '&key=EBJme6M7CYzCfMtjtnsJgudt6mcMjxXl';
      $client = \Drupal::httpClient();

      try {
        $response = $client->get($url);
        $result = json_decode($response->getBody(), TRUE);
        \Drupal::logger('Postcode search response')->notice('<pre>' . print_r($result, 1) . '</pre>');
        foreach ($result['results'] as $i => $item) {
          if ($i === 0) {
            $district = $item['DPA']['LOCAL_CUSTODIAN_CODE_DESCRIPTION'];
            $vid = 'county_district';
            $terms = \Drupal::entityTypeManager()
              ->getStorage('taxonomy_term')
              ->loadByProperties([
                      'vid' => $vid,
                      'name' => $district,
                  ]);
            foreach ($terms as $term) {
              $term_id = $term->id();
            }
            if (empty($term_id)) {
              $district = 'All';
            }
            else {
               $district = $term_id;
            }
            return $district;
          }
        }
      }
      catch (RequestException $e) {
        // log exception
        \Drupal::messenger()->addMessage($this->t('The postcode search service appears to be unavailable.'));
        \Drupal::logger('Postcode search')->error('The postcode search service appears to be unavailable.');
      }
    }
  }

}
