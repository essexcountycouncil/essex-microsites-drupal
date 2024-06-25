<?php

namespace Drupal\Tests\domain_path\Functional;

/**
 * Tests the domain path node aliases saving from edit form.
 *
 * @group domain_path
 */
class DomainPathNodeAliasTest extends DomainPathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
  }

  /**
   * Test domain path node aliases.
   */
  public function testDomainPathNodeAliasesFill() {
    // Set path from Node form.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    foreach ($this->domains as $domain) {
      $domain_alias_value = '/' . $this->randomMachineName(8);
      $edit['domain_path[' . $domain->id() . '][path]'] = $domain_alias_value;
      $domain_paths_check[$domain->id()] = $domain_alias_value;
    }
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    $storage = \Drupal::service('entity_type.manager')->getStorage('domain_path');
    foreach ($domain_paths_check as $domain_id => $domain_alias_value) {
      $this->assertCount(1, $storage->loadByProperties([
        'domain_id' => $domain_id,
        'alias' => $domain_alias_value,
      ]));
    }

    // Check values on node form.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $session = $this->assertSession();
    foreach ($domain_paths_check as $domain_id => $domain_alias_value) {
      $session->fieldValueEquals('domain_path[' . $domain_id . '][path]', $domain_alias_value);
    }
    // Ensure aren't removed on second save.
    $page = $this->getSession()->getPage();
    $page->pressButton('Save');
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $session = $this->assertSession();
    foreach ($domain_paths_check as $domain_id => $domain_alias_value) {
      $session->fieldValueEquals('domain_path[' . $domain_id . '][path]', $domain_alias_value);
    }
  }

}
