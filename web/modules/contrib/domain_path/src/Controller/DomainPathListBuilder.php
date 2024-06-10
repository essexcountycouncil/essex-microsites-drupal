<?php

namespace Drupal\domain_path\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\domain_path\Form\DomainPathFilterForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a list controller for domain_path entity.
 *
 * @ingroup domain_path
 */
class DomainPathListBuilder extends EntityListBuilder {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;


  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('url_generator'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('form_builder')
    );
  }

  /**
   * Constructs a new DomainPathListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type domain_path.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, UrlGeneratorInterface $url_generator, Request $current_request, FormBuilderInterface $form_builder) {
    parent::__construct($entity_type, $storage);
    $this->urlGenerator = $url_generator;
    $this->currentRequest = $current_request;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery();
    $query->accessCheck();

    $search = $this->currentRequest->query->get('search');
    if ($search) {
      $query->condition('alias', $search, 'CONTAINS');
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    // Allow the entity query to sort using the table header.
    $header = $this->buildHeader();
    $query->tableSort($header);

    return $query->execute();
  }


  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Domain Path ID');
    $header['language'] = $this->t('Language');
    $header['domain_id'] = $this->t('Domain ID');
    $header['source'] = $this->t('Source');
    $header['alias'] = $this->t('Alias');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\domain_path\Entity\DomainPath */
    $row['id'] = $entity->id();
    $row['language'] = $entity->get('language')->value;
    $row['domain_id'] = $entity->get('domain_id')->target_id;
    $row['source'] = $entity->get('source')->value;
    $row['alias'] = $entity->get('alias')->value;

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $keys = $this->currentRequest->query->get('search');
    $build['path_admin_filter_form'] = $this->formBuilder->getForm(DomainPathFilterForm::class, $keys);
    $build += parent::render();

    return $build;
  }

}
