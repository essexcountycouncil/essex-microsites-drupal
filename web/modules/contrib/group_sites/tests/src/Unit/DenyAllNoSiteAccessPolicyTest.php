<?php

namespace Drupal\Tests\group_sites\Unit;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\flexible_permissions\CalculatedPermissionsItem;
use Drupal\flexible_permissions\RefinableCalculatedPermissions;
use Drupal\group_sites\Access\DenyAllNoSiteAccessPolicy;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the deny all no site access policy.
 *
 * @coversDefaultClass \Drupal\group_sites\Access\DenyAllNoSiteAccessPolicy
 * @group group_sites
 */
class DenyAllNoSiteAccessPolicyTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $cache_context_manager = $this->prophesize(CacheContextsManager::class);
    $cache_context_manager->assertValidTokens(Argument::any())->willReturn(TRUE);

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('cache_contexts_manager')->willReturn($cache_context_manager->reveal());
    \Drupal::setContainer($container->reveal());
  }

  /**
   * Tests that all permissions are denied.
   *
   * @covers ::alterPermissions
   */
  public function testAlterPermissions() {
    $account = $this->prophesize(AccountInterface::class)->reveal();

    $calculated_permissions = (new RefinableCalculatedPermissions())
      ->addItem(new CalculatedPermissionsItem('anything', 'id', ['some permissions'], FALSE))
      ->addCacheContexts(['foo'])
      ->addCacheTags(['bar']);
    $calculated_permissions->disableBuildMode();

    $access_policy = new DenyAllNoSiteAccessPolicy();
    $access_policy->alterPermissions($account, 'anything', $calculated_permissions);

    $this->assertSame([], $calculated_permissions->getItems());
    $this->assertSame(['foo'], $calculated_permissions->getCacheContexts());
    $this->assertSame(['bar'], $calculated_permissions->getCacheTags());
  }

}
