<?php

namespace Drupal\Tests\group_sites\Unit;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\group_sites\GroupSitesAdminMode;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the group sites admin mode service.
 *
 * @coversDefaultClass \Drupal\group_sites\GroupSitesAdminMode
 * @group group_sites
 */
class GroupSitesAdminModeTest extends UnitTestCase {

  /**
   * Tests whether isActive returns the right mode and checks the permission.
   *
   * @param bool $has_permission
   *   Whether the current user has the admin mode permission.
   * @param bool $in_admin_mode
   *   Whether the internal storage considers the user to be in admin mode.
   * @param bool $override_active
   *   Whether the admin mode override is active.
   * @param bool $expected
   *   The expected return value.
   * @param bool $should_reset
   *   Whether the admin mode should be reset.
   *
   * @covers ::isActive
   * @dataProvider isActiveProvider
   */
  public function testIsActive(bool $has_permission, bool $in_admin_mode, bool $override_active, bool $expected, bool $should_reset = FALSE) {
    $current_user = $this->prophesize(AccountProxyInterface::class);
    $current_user->hasPermission('use group_sites admin mode')->willReturn($has_permission);

    $private_temp_store = $this->prophesize(PrivateTempStore::class);
    $private_temp_store->get('admin_mode')->willReturn($in_admin_mode);

    if ($should_reset || $override_active) {
      $this->assertAdminModeCacheTagInvalidated($current_user);
    }

    if ($should_reset) {
      $private_temp_store->set('admin_mode', FALSE)->shouldBeCalled();
    }

    $private_temp_store_factory = $this->prophesize(PrivateTempStoreFactory::class);
    $private_temp_store_factory->get('group_sites')->willReturn($private_temp_store->reveal());
    $admin_mode_service = new GroupSitesAdminMode($private_temp_store_factory->reveal(), $current_user->reveal());

    if ($override_active) {
      $admin_mode_service->setAdminModeOverride($override_active);
    }

    $this->assertSame($expected, $admin_mode_service->isActive());
  }

  /**
   * Data provider for testIsActive().
   *
   * @return array
   *   A list of testIsActive method arguments.
   */
  public function isActiveProvider(): array {
    $cases['active-with-permission-no-override'] = [
      'has_permission' => TRUE,
      'in_admin_mode' => TRUE,
      'override_active' => FALSE,
      'expected' => TRUE,
    ];

    $cases['inactive-with-permission-no-override'] = [
      'has_permission' => TRUE,
      'in_admin_mode' => FALSE,
      'override_active' => FALSE,
      'expected' => FALSE,
    ];

    $cases['active-without-permission-no-override'] = [
      'has_permission' => FALSE,
      'in_admin_mode' => TRUE,
      'override_active' => FALSE,
      'expected' => FALSE,
      'should_reset' => TRUE,
    ];

    $cases['inactive-without-permission-no-override'] = [
      'has_permission' => FALSE,
      'in_admin_mode' => FALSE,
      'override_active' => FALSE,
      'expected' => FALSE,
    ];

    $cases['active-with-permission-override'] = [
      'has_permission' => TRUE,
      'in_admin_mode' => TRUE,
      'override_active' => TRUE,
      'expected' => TRUE,
    ];

    $cases['inactive-with-permission-override'] = [
      'has_permission' => TRUE,
      'in_admin_mode' => FALSE,
      'override_active' => TRUE,
      'expected' => TRUE,
    ];

    $cases['active-without-permission-override'] = [
      'has_permission' => FALSE,
      'in_admin_mode' => TRUE,
      'override_active' => TRUE,
      'expected' => TRUE,
    ];

    $cases['inactive-without-permission-override'] = [
      'has_permission' => FALSE,
      'in_admin_mode' => FALSE,
      'override_active' => TRUE,
      'expected' => TRUE,
    ];

    return $cases;
  }

  /**
   * Tests whether setting the admin mode works and checks the permission.
   *
   * @param bool $has_permission
   *   Whether the current user has the admin mode permission.
   * @param bool $admin_mode
   *   The admin mode status to set.
   * @param bool $should_set_mode
   *   Whether the admin mode status should be set.
   *
   * @covers ::setAdminMode
   * @dataProvider setAdminModeProvider
   */
  public function testSetAdminMode(bool $has_permission, bool $admin_mode, bool $should_set_mode) {
    $current_user = $this->prophesize(AccountProxyInterface::class);
    $current_user->hasPermission('use group_sites admin mode')->willReturn($has_permission);

    $private_temp_store = $this->prophesize(PrivateTempStore::class);
    if ($should_set_mode) {
      $this->assertAdminModeCacheTagInvalidated($current_user);
      $private_temp_store->set('admin_mode', $admin_mode)->shouldBeCalled();
    }
    else {
      $private_temp_store->set('admin_mode', $admin_mode)->shouldNotBeCalled();
    }

    $private_temp_store_factory = $this->prophesize(PrivateTempStoreFactory::class);
    $private_temp_store_factory->get('group_sites')->willReturn($private_temp_store->reveal());
    $admin_mode_service = new GroupSitesAdminMode($private_temp_store_factory->reveal(), $current_user->reveal());
    $admin_mode_service->setAdminMode($admin_mode);
  }

  /**
   * Data provider for testSetAdminMode().
   *
   * @return array
   *   A list of testSetAdminMode method arguments.
   */
  public function setAdminModeProvider(): array {
    $cases['set-with-permission'] = [
      'has_permission' => TRUE,
      'admin_mode' => TRUE,
      'should_set_mode' => TRUE,
    ];

    $cases['unset-with-permission'] = [
      'has_permission' => TRUE,
      'admin_mode' => FALSE,
      'should_set_mode' => TRUE,
    ];

    $cases['set-without-permission'] = [
      'has_permission' => FALSE,
      'admin_mode' => TRUE,
      'should_set_mode' => FALSE,
    ];

    $cases['unset-without-permission'] = [
      'has_permission' => FALSE,
      'admin_mode' => FALSE,
      'should_set_mode' => TRUE,
    ];

    return $cases;
  }

  /**
   * Checks that the current user's admin mode cache tag is cleared.
   *
   * @param \Prophecy\Prophecy\ObjectProphecy $current_user
   *   The mocked current user.
   */
  protected function assertAdminModeCacheTagInvalidated(ObjectProphecy $current_user): void {
    $current_user->id()->willReturn(rand());

    $cache_tags_invalidator = $this->prophesize(CacheTagsInvalidatorInterface::class);
    $cache_tags_invalidator->invalidateTags(['group_sites:admin_mode:' . $current_user->reveal()->id()])->shouldBeCalled();

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('cache_tags.invalidator')->willReturn($cache_tags_invalidator->reveal());
    \Drupal::setContainer($container->reveal());
  }

}
