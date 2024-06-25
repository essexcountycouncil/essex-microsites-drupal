<?php

namespace Drupal\Tests\domain_group\Kernel;

use Drupal\domain_group\ContextProvider\DomainGroupContext;
use Drupal\domain_group\DomainGroupResolverInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\domain_group\Traits\GroupCreationTrait;
use Drupal\Tests\domain_group\Traits\InitializeGroupsTrait;

/**
 * @coversDefaultClass \Drupal\domain_group\ContextProvider\DomainGroupContext
 *
 * @group domain_group
 */
class DomainGroupContextTest extends EntityKernelTestBase {

  use GroupCreationTrait;
  use InitializeGroupsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'group',
    'flexible_permissions',
    'domain',
    'domain_group',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['group']);
    $this->installEntitySchema('group_relationship');
    $this->installEntitySchema('user');
    $this->installEntitySchema('group');
  }

  /**
   * @covers ::getAvailableContexts
   */
  public function testGetAvailableContexts() {
    $context_repository = $this->container->get('context.repository');

    $contexts = $context_repository->getAvailableContexts();
    $this->assertArrayHasKey('@domain_group.domain_group_context:group', $contexts);
    $this->assertSame('entity:group', $contexts['@domain_group.domain_group_context:group']->getContextDefinition()
      ->getDataType());
  }

  /**
   * @covers ::getRuntimeContexts
   */
  public function testGetRuntimeContexts() {
    $this->createUser();
    $this->setCurrentUser($this->createUser());
    $this->initializeTestGroups();
    $prophecy = $this->prophesize(DomainGroupResolverInterface::class);
    $prophecy->getActiveDomainGroupId()->willReturn(NULL, $this->groupA1->id());
    $domain_group_resolver = $prophecy->reveal();

    $provider = new DomainGroupContext($domain_group_resolver);

    $runtime_contexts = $provider->getRuntimeContexts([]);
    $this->assertArrayHasKey('group', $runtime_contexts);
    $this->assertFalse($runtime_contexts['group']->hasContextValue());
    
    $runtime_contexts = $provider->getRuntimeContexts([]);
    $this->assertArrayHasKey('group', $runtime_contexts);
    $this->assertTrue($runtime_contexts['group']->hasContextValue());
  }

}
