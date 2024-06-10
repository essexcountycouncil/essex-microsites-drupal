<?php

declare(strict_types=1);

namespace Drupal\Tests\group_context_domain\Unit;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_context_domain\Cache\Context\DomainGroupCacheContext;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the url.site.group cache context.
 *
 * @coversDefaultClass \Drupal\group_context_domain\Cache\Context\DomainGroupCacheContext
 * @group group_context_domain
 */
class DomainGroupCacheContextTest extends UnitTestCase {

  /**
   * The mocked domain negotiator service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\domain\DomainNegotiatorInterface>
   */
  protected $domainNegotiator;

  /**
   * The mocked entity repository service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy<\Drupal\Core\Entity\EntityRepositoryInterface>
   */
  protected $entityRepository;

  /**
   * The cache context to test.
   *
   * @var \Drupal\group_context_domain\Cache\Context\DomainGroupCacheContext
   */
  protected $cacheContext;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->domainNegotiator = $this->prophesize(DomainNegotiatorInterface::class);
    $this->entityRepository = $this->prophesize(EntityRepositoryInterface::class);
    $this->cacheContext = new DomainGroupCacheContext($this->domainNegotiator->reveal(), $this->entityRepository->reveal());
  }

  /**
   * Tests getting the context value when there is no domain.
   *
   * @covers ::getContext
   */
  public function testGetContextNoDomain(): void {
    $this->domainNegotiator->getActiveDomain()->willReturn(NULL);
    $this->assertSame('group.none', $this->cacheContext->getContext());
  }

  /**
   * Tests getting the context value when there is no group for the domain.
   *
   * @covers ::getContext
   */
  public function testGetContextNoDomainGroup(): void {
    $domain = $this->prophesize(DomainInterface::class);
    $this->domainNegotiator->getActiveDomain()->willReturn($domain->reveal());
    $domain->getThirdPartySetting('group_context_domain', 'group_uuid')->willReturn(NULL);
    $this->assertSame('group.none', $this->cacheContext->getContext());
  }

  /**
   * Tests getting the context value when there is a group for the domain.
   *
   * @covers ::getContext
   */
  public function testGetContext(): void {
    $domain = $this->prophesize(DomainInterface::class);
    $domain->getThirdPartySetting('group_context_domain', 'group_uuid')->willReturn('foo');
    $this->domainNegotiator->getActiveDomain()->willReturn($domain->reveal());

    $group = $this->prophesize(GroupInterface::class);
    $group->id()->willReturn(1337);
    $this->entityRepository->loadEntityByUuid('group', 'foo')->willReturn($group->reveal());

    $this->assertSame(1337, $this->cacheContext->getContext());
  }

  /**
   * Tests getting the cacheable metadata when there is no domain.
   *
   * @covers ::getCacheableMetadata
   */
  public function testGetCacheableMetadataNoDomain(): void {
    $this->domainNegotiator->getActiveDomain()->willReturn(NULL);
    $this->assertEquals(new CacheableMetadata(), $this->cacheContext->getCacheableMetadata());
  }

  /**
   * Tests getting the cacheable metadata when there is a domain.
   *
   * @covers ::getCacheableMetadata
   */
  public function testGetCacheableMetadata(): void {
    $cache_context_manager = $this->prophesize(CacheContextsManager::class);
    $cache_context_manager->assertValidTokens(Argument::any())->willReturn(TRUE);

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('cache_contexts_manager')->willReturn($cache_context_manager->reveal());
    \Drupal::setContainer($container->reveal());

    $domain = $this->prophesize(DomainInterface::class);
    $domain->getCacheContexts()->willReturn(['foo']);
    $domain->getCacheTags()->willReturn(['bar']);
    $domain->getCacheMaxAge()->willReturn(9001);
    $this->domainNegotiator->getActiveDomain()->willReturn($domain->reveal());

    $expected = (new CacheableMetadata())
      ->setCacheContexts(['foo'])
      ->setCacheTags(['bar'])
      ->setCacheMaxAge(9001);
    $this->assertEquals($expected, $this->cacheContext->getCacheableMetadata());
  }

}
