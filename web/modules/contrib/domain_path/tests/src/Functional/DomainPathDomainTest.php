<?php

namespace Drupal\Tests\domain_path\Functional;

/**
 * Tests an alias on each domain.
 *
 * @group domain_path
 */
class DomainPathDomainTest extends DomainPathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
  }

  /**
   * Tests getting each of the domain paths.
   */
  public function testDomainPathGet() {
    // No domain paths should exist.
    $this->domainPathTableIsEmpty();
    $node = $this->drupalCreateNode();
    $domain_path_storage = \Drupal::service('entity_type.manager')->getStorage('domain_path');

    foreach ($this->domains as $domain) {
      $alias = '/' . $this->randomMachineName(8);
      $domain_path_storage->create([
        'type' => 'domain_path',
        'alias' => $alias,
        'domain_id' => $domain->id(),
        'language' => $node->language()->getId(),
        'source' => '/node/' . $node->id(),
      ])->save();
      $domain_paths[] = [
        'alias' => $domain->getPath() . $alias,
        'path' => $node->id(),
        'text' => $node->getTitle(),
      ];
    }

    foreach ($domain_paths as $domain_path) {
      $this->drupalGet($domain_path['alias']);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->pageTextContains($domain_path['text']);
    }
  }

}
