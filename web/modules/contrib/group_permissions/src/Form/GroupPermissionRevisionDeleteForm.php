<?php

namespace Drupal\group_permissions\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a form for deleting a group permissions revision.
 *
 * @ingroup group_permissions
 */
class GroupPermissionRevisionDeleteForm extends ConfirmFormBase {

  use StringTranslationTrait;

  /**
   * The group permissions revision.
   *
   * @var \Drupal\group_permissions\Entity\GroupPermissionInterface
   */
  protected $revision;

  /**
   * The group permissions storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $groupPermissionStorage;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->groupPermissionStorage = $container->get('entity_type.manager')->getStorage('group_permission');
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->connection = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_permissions_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.group_permission.version-history', [
      'group_permission' => $this->revision->id(),
      'group' => $this->revision->getGroup()->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $group_permission_revision = NULL) {

    $this->revision = $this->groupPermissionStorage->loadRevision($group_permission_revision);
    if (empty($this->revision)) {
      $this->messenger()->addError($this->t('Revision does not exist.'));
      $form_state->setRedirect('<front>');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->groupPermissionStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('group_permissions')->notice('group permissions: deleted %title revision %revision.', [
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId(),
    ]);
    $this->messenger()->addMessage($this->t('Revision from %revision-date of group permissions %title has been deleted.', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
      '%title' => $this->revision->label(),
    ]));
    $form_state->setRedirect('entity.group_permission.version-history', [
      'group_permission' => $this->revision->id(),
      'group' => $this->revision->getGroup()->id(),
    ]);
  }

}
