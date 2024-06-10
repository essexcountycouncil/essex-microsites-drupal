<?php

namespace Drupal\group_sites\Access;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flexible_permissions\PermissionCalculatorAlterInterfaceV2;
use Drupal\flexible_permissions\PermissionCalculatorInterface;
use Drupal\flexible_permissions\RefinableCalculatedPermissions;
use Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\group_sites\GroupSitesAccessPolicyException;
use Drupal\group_sites\GroupSitesAdminModeInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Access policy that runs configured "site" or "no site" access policies.
 */
class GroupSitesAccessPolicy implements PermissionCalculatorInterface, PermissionCalculatorAlterInterfaceV2 {

  use ContainerAwareTrait;
  use StringTranslationTrait;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected ContextRepositoryInterface $contextRepository,
    protected GroupSitesAdminModeInterface $adminMode,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, $scope) {
    return (new RefinableCalculatedPermissions())->addCacheContexts($this->getPersistentCacheContexts($scope));
  }

  /**
   * {@inheritdoc}
   */
  public function alterPermissions(AccountInterface $account, $scope, RefinableCalculatedPermissionsInterface $calculated_permissions) {
    $supported_scopes = [
      PermissionScopeInterface::INDIVIDUAL_ID,
      PermissionScopeInterface::OUTSIDER_ID,
      PermissionScopeInterface::INSIDER_ID,
    ];
    if (!in_array($scope, $supported_scopes, TRUE)) {
      return;
    }

    if ($this->adminMode->isActive()) {
      return;
    }

    $calculated_permissions->addCacheTags(['config:group_sites.settings']);
    if ($context = $this->getContext()) {
      $calculated_permissions->addCacheableDependency($context);
    }

    if ($group = $context?->getContextValue()) {
      if (!$group instanceof GroupInterface) {
        throw new \InvalidArgumentException('Context value is not a Group entity.');
      }
      $this->getSiteAccessPolicy()->alterPermissions($group, $account, $scope, $calculated_permissions);
    }
    else {
      $this->getNoSiteAccessPolicy()->alterPermissions($account, $scope, $calculated_permissions);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts($scope) {
    $supported_scopes = [
      PermissionScopeInterface::INDIVIDUAL_ID,
      PermissionScopeInterface::OUTSIDER_ID,
      PermissionScopeInterface::INSIDER_ID,
    ];
    if (!in_array($scope, $supported_scopes, TRUE)) {
      return [];
    }

    // We only add the flag here as the context actually sets the other cache
    // contexts. This is a perfect use case for cache redirects where we first
    // hand only a single cache context to VariationCache, but depending on
    // embedded context logic, we may end up adding more in alterPermissions().
    return ['user.in_group_sites_admin_mode'];
  }

  /**
   * Gets the configured access policy for when there is no site.
   *
   * @return \Drupal\group_sites\Access\GroupSitesNoSiteAccessPolicyInterface
   *   The access policy.
   */
  protected function getNoSiteAccessPolicy() {
    $access_policy_id = $this->configFactory->get('group_sites.settings')->get('no_site_access_policy');
    $access_policy = $this->container->get($access_policy_id);

    if (!$access_policy instanceof GroupSitesNoSiteAccessPolicyInterface) {
      throw new GroupSitesAccessPolicyException($access_policy_id, GroupSitesNoSiteAccessPolicyInterface::class);
    }

    return $access_policy;
  }

  /**
   * Gets the configured access policy for when there is a site.
   *
   * @return \Drupal\group_sites\Access\GroupSitesSiteAccessPolicyInterface
   *   The access policy.
   */
  protected function getSiteAccessPolicy() {
    $access_policy_id = $this->configFactory->get('group_sites.settings')->get('site_access_policy');
    $access_policy = $this->container->get($access_policy_id);

    if (!$access_policy instanceof GroupSitesSiteAccessPolicyInterface) {
      throw new GroupSitesAccessPolicyException($access_policy_id, GroupSitesSiteAccessPolicyInterface::class);
    }

    return $access_policy;
  }

  /**
   * Tries to retrieve the configured context.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface|null
   *   The context or NULL if none was found.
   */
  protected function getContext(): ContextInterface|null {
    $context_id = $this->configFactory->get('group_sites.settings')->get('context_provider');
    if ($context_id === NULL) {
      return NULL;
    }

    $contexts = $this->contextRepository->getRuntimeContexts([$context_id]);
    return count($contexts) ? reset($contexts) : NULL;
  }

}
