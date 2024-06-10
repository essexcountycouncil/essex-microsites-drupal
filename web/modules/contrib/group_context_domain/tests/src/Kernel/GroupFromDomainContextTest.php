<?php

declare(strict_types=1);

namespace Drupal\Tests\group_context_domain\Kernel;

use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\group\Entity\GroupInterface;

/**
 * Tests the GroupFromDomain context provider.
 *
 * @coversDefaultClass \Drupal\group_context_domain\Context\GroupFromDomainContext
 * @group group_context_domain
 */
class GroupFromDomainContextTest extends GroupContextDomainKernelTestBase {

  /**
   * The context provider to test.
   *
   * @var \Drupal\group_context_domain\Context\GroupFromDomainContext
   */
  protected $contextProvider;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->contextProvider = $this->container->get('group_context_domain.group_from_domain_context');
  }

  /**
   * Tests getting the context value when there is no domain.
   *
   * @covers ::getRuntimeContexts
   */
  public function testGetRuntimeContextsNoDomain() {
    $contexts = $this->contextProvider->getRuntimeContexts(['group']);
    $this->assertCount(1, $contexts);
    $this->assertArrayHasKey('group', $contexts);

    $context = $contexts['group'];
    $this->assertFalse($context->getContextDefinition()->isRequired());
    $this->assertSame(['url.site.group'], $context->getCacheContexts());
    $this->assertNull($context->getContextValue());
  }

  /**
   * Tests getting the context value when there is no group for the domain.
   *
   * @covers ::getRuntimeContexts
   */
  public function testGetRuntimeContextsNoDomainGroup() {
    $domain = $this->createDomain();

    $domain_negotiator = \Drupal::service('domain.negotiator');
    $domain_negotiator->setActiveDomain($domain);
    $this->assertSame($domain, $domain_negotiator->getActiveDomain());

    $contexts = $this->contextProvider->getRuntimeContexts(['group']);
    $this->assertCount(1, $contexts);
    $this->assertArrayHasKey('group', $contexts);

    $context = $contexts['group'];
    $this->assertFalse($context->getContextDefinition()->isRequired());
    $this->assertSame(['url.site.group'], $context->getCacheContexts());
    $this->assertNull($context->getContextValue());
  }

  /**
   * Tests getting the context value when there is a group for the domain.
   *
   * @covers ::getRuntimeContexts
   */
  public function testGetRuntimeContexts() {
    $group_type = $this->createGroupType();
    $group = $this->createGroup(['type' => $group_type->id()]);
    $domain = $this->createDomain([
      'third_party_settings' => [
        'group_context_domain' => [
          'group_uuid' => $group->uuid(),
        ],
      ],
    ]);

    $domain_negotiator = \Drupal::service('domain.negotiator');
    $domain_negotiator->setActiveDomain($domain);
    $this->assertSame($domain, $domain_negotiator->getActiveDomain());

    $contexts = $this->contextProvider->getRuntimeContexts(['group']);
    $this->assertCount(1, $contexts);
    $this->assertArrayHasKey('group', $contexts);

    $context = $contexts['group'];
    $this->assertFalse($context->getContextDefinition()->isRequired());
    $this->assertSame(['url.site.group'], $context->getCacheContexts());
    $this->assertInstanceOf(GroupInterface::class, $context->getContextValue());
    $this->assertSame($group->id(), $context->getContextValue()->id());
  }

  /**
   * Tests getting the available contexts.
   *
   * @covers ::getAvailableContexts
   */
  public function testGetAvailableContexts() {
    $context_definition = new EntityContextDefinition('entity:group', 'Group from domain');
    $context_definition->setDescription('Returns the group from the domain record if there is one. Can be configured on the domain record form.');
    $context = new EntityContext($context_definition);
    $this->assertEquals(['group' => $context], $this->contextProvider->getAvailableContexts());
  }

}
