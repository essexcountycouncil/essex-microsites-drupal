<?php

declare(strict_types=1);

namespace Drupal\Tests\group_context_domain\Kernel;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Tests the DomainGroupUnique constraint's validator.
 *
 * @covers \Drupal\group_context_domain\Plugin\Validation\Constraint\DomainGroupUniqueValidator
 * @group group_context_domain
 */
class DomainGroupUniqueValidatorTest extends GroupContextDomainKernelTestBase {

  /**
   * Tests that the constraint applies correctly.
   */
  public function testConstraint(): void {
    // Save a domain without a group assigned.
    $control = $this->createDomain();
    $this->assertCount(0, $control->getTypedData()->validate());

    // Create two groups and assign them to two different domains.
    $group_type = $this->createGroupType();
    $group_a = $this->createGroup(['type' => $group_type->id()]);
    $group_b = $this->createGroup(['type' => $group_type->id()]);
    $domain_a = $this->createDomain(['third_party_settings' => ['group_context_domain' => ['group_uuid' => $group_a->uuid()]]]);
    $domain_b = $this->createDomain(['third_party_settings' => ['group_context_domain' => ['group_uuid' => $group_b->uuid()]]]);
    $this->assertCount(0, $domain_a->getTypedData()->validate());
    $this->assertCount(0, $domain_b->getTypedData()->validate());

    // Now try to assign one of these groups to another domain.
    $domain_c = $this->createDomain(['third_party_settings' => ['group_context_domain' => ['group_uuid' => $group_a->uuid()]]]);
    $violations = $domain_c->getTypedData()->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals(
      new TranslatableMarkup(
        'The %group_label group is already tied to another domain.',
        ['%group_label' => $group_a->label()]
      ),
      $violations->get(0)->getMessage()
    );
  }

}
