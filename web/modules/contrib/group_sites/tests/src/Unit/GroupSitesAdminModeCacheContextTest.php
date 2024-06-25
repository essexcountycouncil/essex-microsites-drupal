<?php

namespace Drupal\Tests\group_sites\Unit;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group_sites\Cache\GroupSitesAdminModeCacheContext;
use Drupal\group_sites\GroupSitesAdminModeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the user.in_group_sites_admin_mode cache context.
 *
 * @coversDefaultClass \Drupal\group_sites\Cache\GroupSitesAdminModeCacheContext
 * @group group_sites
 */
class GroupSitesAdminModeCacheContextTest extends UnitTestCase {

  /**
   * The mocked admin mode service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\group_sites\GroupSitesAdminModeInterface>
   */
  protected $adminMode;

  /**
   * The mocked current user service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Session\AccountProxyInterface>
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->adminMode = $this->prophesize(GroupSitesAdminModeInterface::class);
    $this->currentUser = $this->prophesize(AccountProxyInterface::class);
  }

  /**
   * Tests getting the context value when admin mode is on and off.
   *
   * @covers ::getContext
   */
  public function testGetContext() {
    $cache_context = new GroupSitesAdminModeCacheContext($this->adminMode->reveal(), $this->currentUser->reveal());

    $this->adminMode->isActive()->willReturn(TRUE);
    $this->assertSame('active', $cache_context->getContext());

    $this->adminMode->isActive()->willReturn(FALSE);
    $this->assertSame('inactive', $cache_context->getContext());
  }

  /**
   * Tests getting the cacheable metadata for the cache context.
   *
   * @covers ::getCacheableMetadata
   */
  public function testGetCacheableMetadata() {
    $this->currentUser->id()->willReturn(1986);
    $cache_context = new GroupSitesAdminModeCacheContext($this->adminMode->reveal(), $this->currentUser->reveal());

    $expected = (new CacheableMetadata())
      ->setCacheTags(['group_sites:admin_mode:1986'])
      ->setCacheMaxAge(0);
    $this->assertEquals($expected, $cache_context->getCacheableMetadata());
  }

}
