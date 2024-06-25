<?php

namespace Drupal\Tests\group_sites\Kernel;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flexible_permissions\CalculatedPermissionsItem;
use Drupal\flexible_permissions\RefinableCalculatedPermissions;
use Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\group_sites\Access\GroupSitesAccessPolicy;
use Drupal\group_sites\Access\GroupSitesNoSiteAccessPolicyInterface;
use Drupal\group_sites\Access\GroupSitesSiteAccessPolicyInterface;
use Drupal\group_sites\GroupSitesAccessPolicyException;
use Drupal\group_sites\GroupSitesAdminModeInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the behavior of the Group Sites access policy.
 *
 * @group group_sites
 */
class GroupSitesAccessPolicyTest extends UnitTestCase {

  /**
   * The mocked config factory.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Config\ConfigFactoryInterface>
   */
  protected $configFactory;

  /**
   * The mocked context repository.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Plugin\Context\ContextRepositoryInterface>
   */
  protected $contextRepository;

  /**
   * The mocked admin mode service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\group_sites\GroupSitesAdminModeInterface>
   */
  protected $adminMode;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpContainer();

    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->contextRepository = $this->prophesize(ContextRepositoryInterface::class);
    $this->adminMode = $this->prophesize(GroupSitesAdminModeInterface::class);
  }

  /**
   * Tests that nothing happens when admin mode is on.
   *
   * @covers ::alterPermissions
   */
  public function testAlterPermissionsWithAdminMode(): void {
    $this->adminMode->isActive()->willReturn(TRUE);
    $this->configFactory->get('group_sites.settings')->shouldNotBeCalled();
    $this->contextRepository->getRuntimeContexts(Argument::any())->shouldNotBeCalled();

    $access_policy = $this->setUpAccessPolicy();
    $calculated_permissions = $this->setUpCalculatedPermissions();

    $test_subject = clone $calculated_permissions;
    $access_policy->alterPermissions($this->prophesize(AccountInterface::class)->reveal(), 'individual', $calculated_permissions);
    $this->assertEquals($calculated_permissions, $test_subject);
  }

  /**
   * Tests that nothing happens with unsupported scope.
   *
   * @covers ::alterPermissions
   */
  public function testAlterPermissionsWithUnsupportedScope(): void {
    $this->adminMode->isActive()->willReturn(FALSE);
    $this->configFactory->get('group_sites.settings')->shouldNotBeCalled();
    $this->contextRepository->getRuntimeContexts(Argument::any())->shouldNotBeCalled();

    $access_policy = $this->setUpAccessPolicy();
    $calculated_permissions = $this->setUpCalculatedPermissions();

    $test_subject = clone $calculated_permissions;
    $access_policy->alterPermissions($this->prophesize(AccountInterface::class)->reveal(), 'unsupported', $calculated_permissions);
    $this->assertEquals($calculated_permissions, $test_subject);
  }

  /**
   * Tests that a no site access policy is called when a context is missing.
   *
   * @covers ::alterPermissions
   */
  public function testAlterPermissionsWithNoContext(): void {
    $this->adminMode->isActive()->willReturn(FALSE);

    $config = $this->prophesize(Config::class);
    $config->get('context_provider')->willReturn(NULL);
    $config->get('no_site_access_policy')->willReturn('some_no_site_access_policy');

    $this->configFactory->get('group_sites.settings')->willReturn($config->reveal());
    $this->contextRepository->getRuntimeContexts(Argument::any())->shouldNotBeCalled();

    $account = $this->prophesize(AccountInterface::class)->reveal();
    $calculated_permissions = $this->setUpCalculatedPermissions();

    $no_site_access_policy = $this->prophesize(GroupSitesNoSiteAccessPolicyInterface::class);
    $no_site_access_policy->alterPermissions($account, 'individual', $calculated_permissions)->shouldBeCalled();
    $this->setUpContainer()->get('some_no_site_access_policy')->willReturn($no_site_access_policy->reveal());

    $access_policy = $this->setUpAccessPolicy();
    $access_policy->alterPermissions($account, 'individual', $calculated_permissions);
  }

  /**
   * Tests that a no site access policy is called when a context has no value.
   *
   * @covers ::alterPermissions
   */
  public function testAlterPermissionsWithNoContextValue(): void {
    $this->adminMode->isActive()->willReturn(FALSE);

    $config = $this->prophesize(Config::class);
    $config->get('context_provider')->willReturn('@some_service:some_context');
    $config->get('no_site_access_policy')->willReturn('some_no_site_access_policy');

    $this->configFactory->get('group_sites.settings')->willReturn($config->reveal());
    $this->contextRepository->getRuntimeContexts(['@some_service:some_context'])->willReturn([$this->setUpContext(NULL)->reveal()]);

    $account = $this->prophesize(AccountInterface::class)->reveal();
    $calculated_permissions = $this->setUpCalculatedPermissions();

    $no_site_access_policy = $this->prophesize(GroupSitesNoSiteAccessPolicyInterface::class);
    $no_site_access_policy->alterPermissions($account, 'individual', $calculated_permissions)->shouldBeCalled();
    $this->setUpContainer()->get('some_no_site_access_policy')->willReturn($no_site_access_policy->reveal());

    $access_policy = $this->setUpAccessPolicy();
    $access_policy->alterPermissions($account, 'individual', $calculated_permissions);
  }

  /**
   * Tests that a site access policy is called when a context a value.
   *
   * @covers ::alterPermissions
   */
  public function testAlterPermissionsWithContextValue(): void {
    $this->adminMode->isActive()->willReturn(FALSE);

    $config = $this->prophesize(Config::class);
    $config->get('context_provider')->willReturn('@some_service:some_context');
    $config->get('site_access_policy')->willReturn('some_site_access_policy');

    $group = $this->prophesize(GroupInterface::class)->reveal();

    $this->configFactory->get('group_sites.settings')->willReturn($config->reveal());
    $this->contextRepository->getRuntimeContexts(['@some_service:some_context'])->willReturn([$this->setUpContext($group)->reveal()]);

    $account = $this->prophesize(AccountInterface::class)->reveal();
    $calculated_permissions = $this->setUpCalculatedPermissions();

    $site_access_policy = $this->prophesize(GroupSitesSiteAccessPolicyInterface::class);
    $site_access_policy->alterPermissions($group, $account, 'individual', $calculated_permissions)->shouldBeCalled();
    $this->setUpContainer()->get('some_site_access_policy')->willReturn($site_access_policy->reveal());

    $access_policy = $this->setUpAccessPolicy();
    $access_policy->alterPermissions($account, 'individual', $calculated_permissions);
  }

  /**
   * Tests that a wrong context value throws an exception.
   *
   * @covers ::alterPermissions
   */
  public function testAlterPermissionsWithWrongContextValue(): void {
    $this->adminMode->isActive()->willReturn(FALSE);

    $config = $this->prophesize(Config::class);
    $config->get('context_provider')->willReturn('@some_service:some_context');

    $this->configFactory->get('group_sites.settings')->willReturn($config->reveal());
    $this->contextRepository
      ->getRuntimeContexts(['@some_service:some_context'])
      ->willReturn([$this->setUpContext($this->prophesize(AccountInterface::class)->reveal())->reveal()]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Context value is not a Group entity.');

    $access_policy = $this->setUpAccessPolicy();
    $access_policy->alterPermissions(
      $this->prophesize(AccountInterface::class)->reveal(),
      'individual',
      $this->setUpCalculatedPermissions()
    );
  }

  /**
   * Tests that a malformed no site access policy throws an exception.
   *
   * @depends testAlterPermissionsWithNoContext
   * @covers ::alterPermissions
   * @uses ::getNoSiteAccessPolicy
   */
  public function testInvalidNoSiteAccessPolicyException(): void {
    $this->adminMode->isActive()->willReturn(FALSE);

    $config = $this->prophesize(Config::class);
    $config->get('context_provider')->willReturn(NULL);
    $config->get('no_site_access_policy')->willReturn('some_no_site_access_policy');

    $this->configFactory->get('group_sites.settings')->willReturn($config->reveal());
    $this->contextRepository->getRuntimeContexts(Argument::any())->shouldNotBeCalled();

    $this->setUpContainer()->get('some_no_site_access_policy')->willReturn($this->prophesize(AccountInterface::class)->reveal());

    $this->expectException(GroupSitesAccessPolicyException::class);
    $this->expectExceptionMessage('Service "some_no_site_access_policy" does not implement Drupal\group_sites\Access\GroupSitesNoSiteAccessPolicyInterface.');

    $this->setUpAccessPolicy()->alterPermissions(
      $this->prophesize(AccountInterface::class)->reveal(),
      'individual',
      $this->setUpCalculatedPermissions()
    );
  }

  /**
   * Tests that a malformed site access policy throws an exception.
   *
   * @depends testAlterPermissionsWithContextValue
   * @covers ::alterPermissions
   * @uses ::getSiteAccessPolicy
   */
  public function testInvalidSiteAccessPolicyException(): void {
    $this->adminMode->isActive()->willReturn(FALSE);

    $config = $this->prophesize(Config::class);
    $config->get('context_provider')->willReturn('@some_service:some_context');
    $config->get('site_access_policy')->willReturn('some_site_access_policy');

    $this->configFactory->get('group_sites.settings')->willReturn($config->reveal());
    $this->contextRepository
      ->getRuntimeContexts(['@some_service:some_context'])
      ->willReturn([$this->setUpContext($this->prophesize(GroupInterface::class)->reveal())->reveal()]);

    $this->setUpContainer()->get('some_site_access_policy')->willReturn($this->prophesize(AccountInterface::class)->reveal());

    $this->expectException(GroupSitesAccessPolicyException::class);
    $this->expectExceptionMessage('Service "some_site_access_policy" does not implement Drupal\group_sites\Access\GroupSitesSiteAccessPolicyInterface.');

    $access_policy = $this->setUpAccessPolicy();
    $access_policy->alterPermissions(
      $this->prophesize(AccountInterface::class)->reveal(),
      'individual',
      $this->setUpCalculatedPermissions()
    );
  }

  /**
   * Sets up a mock context.
   *
   * @param mixed $value
   *   The value the mocked context should return.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Plugin\Context\ContextInterface>
   *   The mocked context.
   */
  protected function setUpContext(mixed $value): ObjectProphecy {
    $context = $this->prophesize(ContextInterface::class);
    $context->getContextValue()->willReturn($value);
    $context->getCacheContexts()->willReturn([]);
    $context->getCacheTags()->willReturn([]);
    $context->getCacheMaxAge()->willReturn(-1);
    return $context;
  }

  /**
   * Sets up the access policy to run tests on.
   *
   * @return \Drupal\group_sites\Access\GroupSitesAccessPolicy
   *   The access policy to run tests on.
   */
  protected function setUpAccessPolicy(): GroupSitesAccessPolicy {
    $access_policy = new GroupSitesAccessPolicy(
      $this->configFactory->reveal(),
      $this->contextRepository->reveal(),
      $this->adminMode->reveal()
    );
    $access_policy->setContainer(\Drupal::getContainer());
    return $access_policy;
  }

  /**
   * Builds some dummy calculated permissions to run tests on.
   *
   * @return \Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface
   *   The dummy calculated permissions.
   */
  protected function setUpCalculatedPermissions(): RefinableCalculatedPermissionsInterface {
    return (new RefinableCalculatedPermissions())
      ->addItem(new CalculatedPermissionsItem(
        PermissionScopeInterface::INDIVIDUAL_ID,
        1986,
        ['edit group']
      ))
      ->addItem(new CalculatedPermissionsItem(
        PermissionScopeInterface::INSIDER_ID,
        'some type',
        ['view group']
      ))
      ->addCacheTags(['foo'])
      ->addCacheContexts(['bar']);
  }

  /**
   * Sets up the mocked container.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy<\Symfony\Component\DependencyInjection\ContainerInterface>
   *   The mocked container so you can add to it.
   *
   * @todo Move to Group module base class.
   */
  protected function setUpContainer(): ObjectProphecy {
    $cache_context_manager = $this->prophesize(CacheContextsManager::class);
    $cache_context_manager->assertValidTokens(Argument::any())->willReturn(TRUE);

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('cache_contexts_manager')->willReturn($cache_context_manager->reveal());
    \Drupal::setContainer($container->reveal());

    return $container;
  }

}
