<?php

namespace Drupal\Tests\domain_group\Kernel;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\domain_group\DomainGroupResolver;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\domain_group\Traits\GroupCreationTrait;
use Drupal\Tests\domain_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * @coversDefaultClass \Drupal\domain_group\DomainGroupResolver
 *
 * @group domain_group
 */
class DomainGroupResolverTest extends EntityKernelTestBase {

  // https://www.drupal.org/project/domain_site_settings/issues/3204455
  protected $strictConfigSchema = FALSE;

  use GroupCreationTrait;
  use InitializeGroupsTrait;

  use NodeCreationTrait {
    getNodeByTitle as drupalGetNodeByTitle;
    createNode as drupalCreateNode;
  }
  use ContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'system',
    'filter',
    'text',
    'group',
    'flexible_permissions',
    'domain',
    'domain_group',
    'gnode',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('group_relationship');
    $this->installEntitySchema('user');
    $this->installEntitySchema('group');
    $this->installSchema('node', ['node_access']);
    $this->installConfig('filter');
    $this->installConfig('node');
    $this->installConfig('group');
    $this->installConfig(['group', 'gnode', 'domain_group']);

    $this->groupUser = $this->createUser();
    $this->setCurrentUser($this->groupUser);
    $this->initializeTestGroups();
    $this->initializeTestGroupsDomains();
    $this->initializeTestGroupRelationship();
  }

  /**
   * @covers ::getActiveDomainGroupId
   */
  public function testGetActiveDomainGroup() {
    $domain_storage = $this->getEntityTypeManager()->getStorage('domain');
    $default = $domain_storage->loadDefaultDomain();
    $ga1_domain = $domain_storage->load('group_' . $this->groupA1->id());
    $prophecy = $this->prophesize(DomainNegotiatorInterface::class);
    $prophecy->getActiveDomain()->willReturn(NULL, $default, $ga1_domain);
    $domain_negotiator = $prophecy->reveal();

    $prophecy = $this->prophesize(RouteMatchInterface::class);
    $current_route_match = $prophecy->reveal();

    $service = new DomainGroupResolver($this->getEntityTypeManager(), $domain_negotiator, $current_route_match);

    $gid = $service->getActiveDomainGroupId();
    $this->assertNull($gid);
    $gid = $service->getActiveDomainGroupId();
    $this->assertNull($gid);
    $gid = $service->getActiveDomainGroupId();
    $this->assertEquals($this->groupA1->id(), $gid);
  }

  /**
   * @covers ::getEntityGroupDomains
   */
  public function testGetEntityGroupDomains() {
    $prophecy = $this->prophesize(DomainNegotiatorInterface::class);
    $domain_negotiator = $prophecy->reveal();
    $prophecy = $this->prophesize(RouteMatchInterface::class);
    $current_route_match = $prophecy->reveal();
    $service = new DomainGroupResolver($this->getEntityTypeManager(), $domain_negotiator, $current_route_match);

    // Entity in a group.
    $domain_storage = $this->getEntityTypeManager()->getStorage('domain');
    $ga1_domain = $domain_storage->load('group_' . $this->groupA1->id());
    $this->assertEquals(['group_1' => $ga1_domain], $service->getEntityGroupDomains($this->nodeA1));

    // Entity not in a group.
    $node = $this->drupalCreateNode();
    $this->assertEquals([], $service->getEntityGroupDomains($node));

    // Entity in multiple groups.
    $test_groups = [];
    foreach ($this->allTestGroups as $id => $group) {
      $test_groups['group_' . $group->id()] = $this->getEntityTypeManager()->getStorage('domain')->load('group_' . $group->id());
    }
    $this->assertEquals($test_groups, $service->getEntityGroupDomains($this->groupUser));

    // GroupRelationship entity itself.
    $group_relationship = $this->groupA1->getRelationshipsByEntity($this->nodeA1);
    $this->assertEquals(['group_1' => $ga1_domain], $service->getEntityGroupDomains(reset($group_relationship)));

    // Group itself.
    $this->assertEquals(['group_1' => $ga1_domain], $service->getEntityGroupDomains($this->groupA1));
  }

  /**
   * @covers ::getCurrentRouteGroupDomain
   */
  public function testGetCurrentRouteGroupDomain() {
    $prophecy = $this->prophesize(DomainNegotiatorInterface::class);
    $domain_negotiator = $prophecy->reveal();
    $prophecy = $this->prophesize(RouteMatchInterface::class);
    $prophecy->getParameter('group')->willReturn($this->groupA1, NULL, NULL);
    $prophecy->getParameter('group_type')->willReturn($this->groupTypeB);
    $prophecy->getRouteName()->willReturn('entity.group.add_form', NULL);
    $current_route_match = $prophecy->reveal();
    $service = new DomainGroupResolver($this->getEntityTypeManager(), $domain_negotiator, $current_route_match);

    // Group.
    $this->assertEquals($this->getEntityTypeManager()->getStorage('domain')->load('group_' . $this->groupA1->id()), $service->getCurrentRouteGroupDomain());
    // Group add form.
    $this->assertEquals(NULL, $service->getCurrentRouteGroupDomain());
    // Neither.
    $this->assertNull($service->getCurrentRouteGroupDomain());
  }

}
