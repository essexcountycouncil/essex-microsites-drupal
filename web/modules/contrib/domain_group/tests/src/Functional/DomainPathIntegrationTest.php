<?php

namespace Drupal\Tests\domain_group\Functional;

use Drupal\domain\DomainInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\domain_group\Traits\GroupCreationTrait;
use Drupal\Tests\domain_group\Traits\InitializeGroupsTrait;
use Drupal\user\RoleInterface;

/**
 * Tests integration with domain_path.
 *
 * @group domain_group
 */
class DomainPathIntegrationTest extends BrowserTestBase {

  use GroupCreationTrait;
  use InitializeGroupsTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';
  
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'domain_path',
    'field',
    'node',
    'gnode',
    'user',
    'path',
    'system',
    'group',
    'domain_group',
  ];


  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin = $this->drupalCreateUser([
      'administer url aliases',
      'administer domain paths',
      'administer nodes',
      'bypass node access',
      'edit domain path entity',
      'add domain paths',
      'edit domain paths',
      'delete domain paths',
      'administer group',
      'domain group settings',
    ]);
    $this->drupalLogin($admin);

    $this->initializeTestGroups(
      ['uid' => $admin->id()],
      ['uid' => $admin->id()],
      ['uid' => $admin->id()],
    );
    $this->initializeTestGroupsDomains();
    $this->initializeTestGroupRelationship();
    // Allow member to view, edit, delete group content article of type A.
    $this->groupTypeARole = $this->createGroupRole([
      'group_type' => $this->groupTypeA->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => [
        'view group',
        'access content overview',
        'administer group',
        'administer members',
        'create group_node:article entity',
        'view group_node:article entity',
        'update any group_node:article entity',
        'delete any group_node:article entity',
      ],
    ]);


    $this->config('domain_path.settings')
      ->set('entity_types', ['node' => TRUE])->save();
  }

  /**
   * Tests getting each of the domain paths.
   */
  public function testNodeForm() {
    $all_domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    foreach ($all_domains as $domain) {
      if ($domain->isDefault()) {
        $default_domain = $domain;
        break;
      }
    }
    $groupA1_domain = $all_domains['group_' . $this->groupA1->id()];
    assert($groupA1_domain instanceof DomainInterface);

    // Node form outside group context.
    // All domain path fields available.
    $domain_paths_check = [];
    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();
    $article_title = $this->randomString();
    $page->fillField('title[0][value]', $article_title);
    foreach ($all_domains as $domain) {
      $domain_alias_value = '/' . $this->randomMachineName(8);
      $page->fillField('domain_path[' . $domain->id() . '][path]', $domain_alias_value);
      $domain_paths_check[$domain->id()] = $domain_alias_value;
    }
    $page->pressButton('edit-submit');
    // Check they got saved.
    $node = $this->drupalGetNodeByTitle($article_title);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $session = $this->assertSession();
    foreach ($domain_paths_check as $domain_id => $domain_alias_value) {
      $session->fieldValueEquals('domain_path[' . $domain_id . '][path]', $domain_alias_value);
    }

    // Default domain, create group content.
    // Just default and group domain path fields.
    $domain_paths_check = [];
    $this->drupalGet('group/'. $this->groupA1->id() . '/content/create/group_node%3Aarticle');
    $article_title = $this->randomString();
    $page->fillField('title[0][value]', $article_title);
    $active_domains = [
      $default_domain->id() => $default_domain,
      'group_' . $this->groupA1->id() => $groupA1_domain,
    ];
    $inactive_domains = $all_domains;
    unset($inactive_domains[$default_domain->id()]);
    unset($inactive_domains['group_' . $this->groupA1->id()]);
    foreach ($active_domains as $domain) {
      $domain_alias_value = '/' . $this->randomMachineName(8);
      $page->fillField($this->domainPathField($domain->id()), $domain_alias_value);
      $domain_paths_check[$domain->id()] = $domain_alias_value;
    }
    foreach ($inactive_domains as $domain) {
      $session->fieldNotExists($this->domainPathField($domain->id()));
    }
    $page->pressButton('edit-submit');
    // Check they got saved.
    $node = $this->drupalGetNodeByTitle($article_title);
    $this->drupalGet('node/' . $node->id() . '/edit');
    foreach ($domain_paths_check as $domain_id => $domain_alias_value) {
      $session->fieldValueEquals($this->domainPathField($domain_id), $domain_alias_value);
    }
    $page->pressButton('edit-submit');
    // Content own own domain.
    // Should only show own domain.
    $this->drupalGet($groupA1_domain->getPath() . 'node/' . $node->id() . '/edit');
    unset($active_domains[$default_domain->id()]);
    $inactive_domains[$default_domain->id()] = $default_domain;
    foreach ($inactive_domains as $domain) {
      $session->fieldNotExists($this->domainPathField($domain->id()));
    }
    $session->fieldValueEquals($this->domainPathField($groupA1_domain->id()), $domain_paths_check['group_' . $this->groupA1->id()]);
    // Change value.
    $domain_paths_check[$groupA1_domain->id()] = '/' . $this->randomMachineName();
    $page->fillField($this->domainPathField($groupA1_domain->id()), $domain_paths_check[$groupA1_domain->id()]);
    $page->pressButton('edit-submit');
    // Check it on the domain.
    $this->drupalGet($groupA1_domain->getPath() . 'node/' . $node->id() . '/edit');
    $session->fieldValueEquals($this->domainPathField($groupA1_domain->id()), $domain_paths_check['group_' . $this->groupA1->id()]);
    // Check values, including those not displayed, were maintained on the
    // default domain.
    $this->drupalGet('node/' . $node->id() . '/edit');
    foreach ($domain_paths_check as $domain_id => $domain_alias_value) {
      $session->fieldValueEquals($this->domainPathField($domain_id), $domain_alias_value);
    }

    // New content on a group domain.
    $groupA3_domain = $all_domains['group_' . $this->groupA3->id()];
    assert($groupA3_domain instanceof DomainInterface);
    $domain_paths_check = [];
    $this->drupalGet($groupA3_domain->getPath() . 'group/'. $this->groupA3->id() . '/content/create/group_node%3Aarticle');
    $article_title = $this->randomString();
    $page->fillField('title[0][value]', $article_title);
    $active_domains = [
      'group_' . $this->groupA3->id() => $groupA3_domain,
    ];
    $inactive_domains = $all_domains;
    unset($inactive_domains['group_' . $this->groupA3->id()]);
    foreach ($active_domains as $domain) {
      $domain_alias_value = '/' . $this->randomMachineName(8);
      $page->fillField($this->domainPathField($domain->id()), $domain_alias_value);
      $domain_paths_check[$domain->id()] = $domain_alias_value;
    }
    foreach ($inactive_domains as $domain) {
      $session->fieldNotExists($this->domainPathField($domain->id()));
    }
    $page->pressButton('edit-submit');
    // Check they got saved.
    $node = $this->drupalGetNodeByTitle($article_title);
    $this->drupalGet($groupA3_domain->getPath() . 'node/' . $node->id() . '/edit');
    foreach ($domain_paths_check as $domain_id => $domain_alias_value) {
      $session->fieldValueEquals($this->domainPathField($domain_id), $domain_alias_value);
    }
    $page->pressButton('edit-submit');
    // And for completeness, that content on default domain.
    $this->drupalGet('node/' . $node->id() . '/edit');
    foreach ($domain_paths_check as $domain_id => $domain_alias_value) {
      $session->fieldValueEquals($this->domainPathField($domain_id), $domain_alias_value);
    }
    unset($inactive_domains[$default_domain->id()]);
    foreach ($inactive_domains as $domain) {
      $session->fieldNotExists($this->domainPathField($domain->id()));
    }
    foreach ($domain_paths_check as $domain_id => $domain_alias_value) {
      $session->fieldValueEquals($this->domainPathField($domain_id), $domain_alias_value);
    }
    $session->fieldValueEquals($this->domainPathField($default_domain->id()), '');
  }

  private function domainPathField($domain_id, $property = 'path') {
    return 'domain_path[' . $domain_id . '][' . $property . ']';
  }

}
