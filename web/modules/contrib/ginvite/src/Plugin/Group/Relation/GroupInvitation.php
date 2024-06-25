<?php

namespace Drupal\ginvite\Plugin\Group\Relation;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a group relation for invitations.
 *
 * @GroupRelationType(
 *   id = "group_invitation",
 *   label = @Translation("Group Invitation"),
 *   description = @Translation("Creates invitations to group."),
 *   entity_type_id = "user",
 *   pretty_path_key = "invitee",
 *   reference_label = @Translation("Invitee"),
 *   reference_description = @Translation("Invited user."),
 * )
 */
class GroupInvitation extends GroupRelationBase implements ContainerFactoryPluginInterface {

  /**
   * Invitation created and waiting for user's response.
   */
  const INVITATION_PENDING = 0;

  /**
   * Invitation accepted by user.
   */
  const INVITATION_ACCEPTED = 1;

  /**
   * Invitation rejected by user.
   */
  const INVITATION_REJECTED = 2;

  /**
   * Invitation expired automatically.
   */
  const INVITATION_EXPIRED = 3;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $static = new static($configuration, $plugin_id, $plugin_definition);

    $static->currentUser = $container->get('current_user');

    return $static;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $body_message = 'Hi there!' . "\n\n";
    $body_message .= '[current-user:name] has invited you to become a member of the group [group:title] on [site:name].' . "\n";
    $body_message .= 'If you wish to accept the invitation, you need to create an account first.' . "\n\n";
    $body_message .= 'Please visit the following address in order to do so: [group_relationship:register_link]' . "\n";
    $body_message .= 'Kind regards,' . "\n";
    $body_message .= 'The [site:name] team';

    $body_message_existing_user = 'Hi there!' . "\n\n";
    $body_message_existing_user .= '[current-user:name] has invited you to become a member of the group [group:title] on [site:name].' . "\n";
    $body_message_existing_user .= 'If you wish to accept the invitation, go to My invitations tab in user profile.' . "\n\n";
    $body_message_existing_user .= 'Please visit the following address in order to do so: [group_relationship:my_invitations_link]' . "\n";
    $body_message_existing_user .= 'Kind regards,' . "\n";
    $body_message_existing_user .= 'The [site:name] team';

    $body_message_cancel = 'Hi there!' . "\n\n";
    $body_message_cancel .= 'Your invitation to the group [group:title] on [site:name] has been cancelled' . "\n";
    $body_message_cancel .= 'Kind regards,' . "\n";
    $body_message_cancel .= 'The [site:name] team';

    return [
      'group_cardinality' => 0,
      'entity_cardinality' => 0,
      'use_creation_wizard' => 0,
      'unblock_invitees' => 1,
      'invitation_subject' => 'You have a pending group invitation',
      'invitation_body' => $body_message,
      'existing_user_invitation_subject' => 'You have a pending group invitation',
      'existing_user_invitation_body' => $body_message_existing_user,
      'send_email_existing_users' => 0,
      'cancel_user_invitation_subject' => 'Your invitation is no longer available',
      'cancel_user_invitation_body' => $body_message_cancel,
      'send_cancel_email' => FALSE,
      'invitation_bypass_form' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $configuration = $this->getConfiguration();
    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other group relationship type plugins.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    $form['group_cardinality']['#disabled'] = TRUE;
    $form['group_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    $form['unblock_invitees'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unblock registered users coming from an invitation'),
      '#default_value' => $configuration['unblock_invitees'],
      '#disabled' => !$this->currentUser->hasPermission('administer account settings'),
    ];

    $form['invitation_bypass_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Accept invitations immediately'),
      '#description' => $this->t('When accepting an invitation, the group membership entity form will be rendered. Enabling this option will bypass this step and the membership will be generated immediately. This is especially useful if your membership entity provides no configuration at all and the invitation accept route renders an empty form with a single submit button.'),
      '#default_value' => $configuration['invitation_bypass_form'],
    ];

    $form['invitation_expire'] = [
      '#type' => 'number',
      '#title' => $this->t('Expire invites'),
      '#default_value' => $configuration['invitation_expire'] ?? '',
      '#description' => $this->t('Automatically expire open invites after the specified days. If left empty invites will not expire.'),
    ];

    $form['invitation_expire_keep'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Keep expired invitations'),
      '#default_value' => $configuration['invitation_expire_keep'] ?? FALSE,
      '#description' => $this->t('If checked, the expired invitations will be kept with a expired status instead of removed.'),
    ];

    // Invitation Email Configuration.
    $form['invitation_email_config'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['invitation_email'] = [
      '#type' => 'details',
      '#title' => $this->t('Invitation e-mail'),
      '#group' => 'invitation_email_config',
      '#open' => TRUE,
    ];
    $form['invitation_email']['invitation_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $configuration['invitation_subject'],
      '#maxlength' => 180,
    ];
    $form['invitation_email']['invitation_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $configuration['invitation_body'],
      '#rows' => 15,
    ];

    $form['existing_user_invitation_email'] = [
      '#type' => 'details',
      '#title' => $this->t('Invitation e-mail for registered users'),
      '#group' => 'invitation_email_config',
    ];
    $form['existing_user_invitation_email']['existing_user_invitation_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $configuration['existing_user_invitation_subject'],
      '#maxlength' => 180,
    ];
    $form['existing_user_invitation_email']['existing_user_invitation_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $configuration['existing_user_invitation_body'],
      '#rows' => 15,
    ];
    $form['existing_user_invitation_email']['send_email_existing_users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send invitation e-mail to already registered users'),
      '#default_value' => $configuration['send_email_existing_users'],
    ];

    $form['cancel_user_invitation_email'] = [
      '#type' => 'details',
      '#title' => $this->t('Invitation cancelled notification'),
      '#group' => 'invitation_email_config',
    ];
    $form['cancel_user_invitation_email']['cancel_user_invitation_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $configuration['cancel_user_invitation_subject'],
      '#maxlength' => 180,
    ];
    $form['cancel_user_invitation_email']['cancel_user_invitation_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $configuration['cancel_user_invitation_body'],
      '#rows' => 15,
    ];
    $form['cancel_user_invitation_email']['send_cancel_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send notification when an invitation is cancelled'),
      '#default_value' => $configuration['send_cancel_email'],
    ];

    $form['token_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => [
        'group',
        'user',
        'group_relationship',
      ],
    ];

    return $form;
  }

}
