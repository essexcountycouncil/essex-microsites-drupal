<?php

namespace Drupal\Tests\group_sites\Unit;

use Drupal\group_sites\Access\GroupSitesNoSiteAccessPolicyInterface;
use Drupal\group_sites\Access\GroupSitesSiteAccessPolicyInterface;
use Drupal\group_sites\GroupSitesAccessPolicyRepository;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the access policy repository.
 *
 * @coversDefaultClass \Drupal\group_sites\GroupSitesAccessPolicyRepository
 * @group group_sites
 */
class GroupSitesAccessPolicyRepositoryTest extends UnitTestCase {

  /**
   * Tests that no site access policies are added and returned correctly.
   *
   * @covers ::addNoSiteAccessPolicy
   * @covers ::getNoSiteAccessPolicies
   */
  public function testNoSiteAccessPolicies() {
    $repository = new GroupSitesAccessPolicyRepository();
    $policy_a = $this->prophesize(GroupSitesNoSiteAccessPolicyInterface::class)->reveal();
    $policy_b = $this->prophesize(GroupSitesNoSiteAccessPolicyInterface::class)->reveal();

    $repository->addNoSiteAccessPolicy($policy_a, 'policy_a');
    $repository->addNoSiteAccessPolicy($policy_b, 'policy_b');

    $expected = [
      'policy_a' => $policy_a,
      'policy_b' => $policy_b,
    ];

    $this->assertSame($expected, $repository->getNoSiteAccessPolicies());
  }

  /**
   * Tests that site access policies are added and returned correctly.
   *
   * @covers ::addSiteAccessPolicy
   * @covers ::getSiteAccessPolicies
   */
  public function testSiteAccessPolicies() {
    $repository = new GroupSitesAccessPolicyRepository();
    $policy_a = $this->prophesize(GroupSitesSiteAccessPolicyInterface::class)->reveal();
    $policy_b = $this->prophesize(GroupSitesSiteAccessPolicyInterface::class)->reveal();

    $repository->addSiteAccessPolicy($policy_a, 'policy_a');
    $repository->addSiteAccessPolicy($policy_b, 'policy_b');

    $expected = [
      'policy_a' => $policy_a,
      'policy_b' => $policy_b,
    ];

    $this->assertSame($expected, $repository->getSiteAccessPolicies());
  }

}
