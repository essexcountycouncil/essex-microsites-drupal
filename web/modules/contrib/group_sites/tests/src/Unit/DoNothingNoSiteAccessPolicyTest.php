<?php

namespace Drupal\Tests\group_sites\Unit;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\flexible_permissions\CalculatedPermissionsItem;
use Drupal\flexible_permissions\RefinableCalculatedPermissions;
use Drupal\group_sites\Access\DoNothingNoSiteAccessPolicy;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the do nothing no site access policy.
 *
 * @coversDefaultClass \Drupal\group_sites\Access\DoNothingNoSiteAccessPolicy
 * @group group_sites
 */
class DoNothingNoSiteAccessPolicyTest extends UnitTestCase {

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
   * Tests that nothing is changed.
   *
   * @covers ::alterPermissions
   */
  public function testAlterPermissions() {
    $account = $this->prophesize(AccountInterface::class)->reveal();

    $calculated_permissions_item = new CalculatedPermissionsItem('anything', 'id', ['some permissions'], FALSE);
    $calculated_permissions = (new RefinableCalculatedPermissions())
      ->addItem($calculated_permissions_item)
      ->addCacheContexts(['foo'])
      ->addCacheTags(['bar']);
    $calculated_permissions->disableBuildMode();

    $access_policy = new DoNothingNoSiteAccessPolicy();
    $access_policy->alterPermissions($account, 'anything', $calculated_permissions);

    $this->assertSame([$calculated_permissions_item], $calculated_permissions->getItems());
    $this->assertSame(['foo'], $calculated_permissions->getCacheContexts());
    $this->assertSame(['bar'], $calculated_permissions->getCacheTags());
  }

}
