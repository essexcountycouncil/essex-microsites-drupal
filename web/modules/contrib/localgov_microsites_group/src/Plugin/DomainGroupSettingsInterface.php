<?php

namespace Drupal\localgov_microsites_group\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Defines an interface for Domain group settings plugins.
 */
interface DomainGroupSettingsInterface extends PluginInspectionInterface {

  /**
   * Returns the administrative label for the plugin.
   *
   * @return string
   *   The plugin label.
   */
  public function getLabel();

  /**
   * Returns the plugin provider.
   *
   * @return string
   *   The plugin provider.
   */
  public function getProvider();

  /**
   * Checks access to form part for account.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for the domain.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return Drupal\Core\Access\AccessResultInterface
   *   If the account has access.
   */
  public function access(GroupInterface $group, AccountInterface $account);

}
