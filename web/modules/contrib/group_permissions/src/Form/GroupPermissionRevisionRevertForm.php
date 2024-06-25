<?php

namespace Drupal\group_permissions\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\group_permissions\Entity\GroupPermissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a group permission revision.
 *
 * @internal
 */
class GroupPermissionRevisionRevertForm extends ConfirmFormBase {

  /**
   * The group permission revision.
   *
   * @var \Drupal\group_permissions\Entity\GroupPermissionInterface
   */
  protected $revision;

  /**
   * The group permission storage.
   *
   * @var \Drupal\group_permissions\Entity\Storage\GroupPermissionStorageInterface
   */
  protected $groupPermissionStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new GroupPermissionRevisionRevertForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $group_permission_storage
   *   The group permission storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityStorageInterface $group_permission_storage, DateFormatterInterface $date_formatter, TimeInterface $time) {
    $this->groupPermissionStorage = $group_permission_storage;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('group_permission'),
      $container->get('date.formatter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_permission_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to revert to the revision from %revision-date?', ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime())]);
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
    return $this->t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $group_permission_revision = NULL) {
    $this->revision = $this->groupPermissionStorage->loadRevision($group_permission_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $this->revision = $this->prepareRevertedRevision($this->revision, $form_state);
    $this->revision->revision_log_message = $this->t('Copy of the revision from %date.', ['%date' => $this->dateFormatter->format($original_revision_timestamp)]);
    $this->revision->setRevisionUserId($this->currentUser()->id());
    $this->revision->setRevisionCreationTime($this->time->getRequestTime());
    $this->revision->setChangedTime($this->time->getRequestTime());

    $this->revision->validate();
    $this->revision->save();

    $this->logger('group_permissions')->notice('Group permission (%revision) %log %date has been reverted.', [
      '%log' => $this->revision->revision_log_message,
      '%date' => $original_revision_timestamp,
      '%revision' => $this->revision->getRevisionId(),
    ]);
    $this->messenger()
      ->addStatus($this->t('%log has been reverted to the revision from %revision-date.', [
        '%log' => $this->revision->revision_log_message,
        '%revision-date' => $this->dateFormatter->format($original_revision_timestamp),
      ]));
    $form_state->setRedirect(
      'entity.group_permission.version-history',
      [
        'group_permission' => $this->revision->id(),
        'group' => $this->revision->getGroup()->id(),
      ]
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\group_permissions\Entity\GroupPermissionInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\group_permissions\Entity\GroupPermissionInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(GroupPermissionInterface $revision, FormStateInterface $form_state) {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);

    return $revision;
  }

}
