<?php

namespace Drupal\ecc_waste\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\URL;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides a ECC Waste form.
 */
class PostcodeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ecc_waste_postcode_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['field_postcode'] = [
      '#type' => 'textfield',
      '#title' => t('Postcode'),
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getUserInput();
    if (!empty($values['field_postcode'])) {
      $url = 'https://api.os.uk/search/places/v1/postcode?postcode=' . $values['field_postcode'] . '&key=EBJme6M7CYzCfMtjtnsJgudt6mcMjxXl';
      $client = \Drupal::httpClient();

      try {
        $response = $client->get($url);
        $result = json_decode($response->getBody(), TRUE);
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
              $district = 'any';
            }
            else {
               $district = $term_id;
            }
            $form_state->setRedirect('view.disposal_options.page_disposal_search', [], ['query' => ['field_disposal_option_districts_target_id' => $district]]);
          }
        }
      }
      catch (RequestException $e) {
        // log exception
        \Drupal::messenger()->addMessage($this->t('The postcode search service appears to be unavailable.'));
      }
    }
  }

}
