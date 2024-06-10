<?php

namespace Drupal\Tests\localgov_microsites_group_term_ui\Functional;

use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests managing group terms.
 *
 * @group localgov_microsites_group_term_ui
 */
class ManageGroupTermsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'localgov_microsites_group_term_ui_test',
  ];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The default group.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');

    // Create a group.
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->group = $this->entityTypeManager->getStorage('group')->create([
      'type' => 'default',
      'label' => $this->randomMachineName(),
    ]);
    $this->group->enforceIsNew();
    $this->group->save();
  }

  /**
   * Test UI to manage terms as group content.
   */
  public function testGroupTermUi() {

    $user = $this->drupalCreateUser(['use group_sites admin mode']);
    $this->group->addMember($user, ['group_roles' => ['default-admin']]);
    $this->drupalLogin($user);
    // Disable group_sites enforcement.
    \Drupal::service('group_sites.admin_mode')->setAdminMode(TRUE);

    $this->drupalGet($this->group->toUrl()->toString() . '/edit');
    $this->assertSession()->pageTextContains('Taxonomies');
    $this->clickLink('Taxonomies');
    $this->assertSession()->pageTextContains('Topic');
    $this->clickLink('Topic');
    $this->assertSession()->pageTextContains('No terms available.');
    $this->clickLink('Add term');
    $term_name = $this->randomString();
    $this->submitForm([
      'name[0][value]' => $term_name,
    ], 'Save and go to list');
    $this->assertSession()->pageTextContains('Created new term ' . $term_name);
    $this->clickLink('edit');
    $new_name = $this->randomString();
    $this->submitForm([
      'name[0][value]' => $new_name,
    ], 'Save');
    $this->assertSession()->pageTextContains('Updated term ' . $new_name);
    $this->clickLink('delete');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains('Deleted term ' . $new_name);
    $this->assertSession()->pageTextContains('No terms available.');
  }

  /**
   * Test permissions for managing terms.
   */
  public function testGroupTermPermissions() {

    // Create a term.
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => 'localgov_topic',
    ]);
    $term->save();
    $this->group->addRelationship($term, 'group_term:localgov_topic');

    // Login as a group member.
    $user = $this->drupalCreateUser(['use group_sites admin mode']);
    $this->group->addMember($user, ['group_roles' => ['default-member']]);
    $this->drupalLogin($user);
    \Drupal::service('group_sites.admin_mode')->setAdminMode(TRUE);

    // Check access to taxonomy management page.
    $taxonomy_url = Url::fromRoute('localgov_microsites_group_term_ui.taxononmy.list',
      [
        'group' => $this->group->id(),
      ]);
    $this->drupalGet($taxonomy_url);
    $this->assertSession()->statusCodeEquals(403);
    $member_role = $this->entityTypeManager
      ->getStorage('group_role')
      ->load('default-member');
    $member_role->grantPermission('access group_term overview')->save();
    $this->drupalGet($taxonomy_url);
    $this->assertSession()->statusCodeEquals(200);

    // Check term management.
    $member_role->grantPermission('view group_term:localgov_topic entity')->save();
    $term_url = Url::fromRoute('view.lgms_group_taxonomy_terms.page',
      [
        'group' => $this->group->id(),
        'vid' => 'localgov_topic',
      ]);
    $this->drupalGet($term_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($term->label());
    $this->assertSession()->pageTextNotContains('Add term');
    $this->assertSession()->pageTextNotContains('Edit');
    $this->assertSession()->pageTextNotContains('Delete');

    // Check add permission.
    $term_add_url = Url::fromRoute('localgov_microsites_group_term_ui.taxononmy.add',
      [
        'group' => $this->group->id(),
        'vid' => 'localgov_topic',
      ]);
    $this->drupalGet($term_add_url);
    $this->assertSession()->statusCodeEquals(403);
    $member_role->grantPermission('create group_term:localgov_topic entity')->save();
    // Group pages have caching issues when changing permissions.
    $this->container->get('cache.render')->invalidateAll();
    $this->drupalGet($term_url);
    $this->assertSession()->pageTextContains('Add term');
    $this->drupalGet($term_add_url);
    $this->assertSession()->statusCodeEquals(200);

    // Check update permission.
    $term_edit_url = Url::fromRoute('entity.taxonomy_term.edit_form',
      [
        'taxonomy_term' => $term->id(),
      ]);
    $this->drupalGet($term_edit_url);
    $this->assertSession()->statusCodeEquals(403);
    $member_role->grantPermission('update any group_term:localgov_topic entity')->save();
    $this->container->get('cache.render')->invalidateAll();
    $this->drupalGet($term_url);
    $this->assertSession()->pageTextContains('Edit');
    $this->drupalGet($term_edit_url);
    $this->assertSession()->statusCodeEquals(200);

    // Check delete permission.
    $term_delete_url = Url::fromRoute('entity.taxonomy_term.delete_form',
      [
        'taxonomy_term' => $term->id(),
      ]);
    $this->drupalGet($term_delete_url);
    $this->assertSession()->statusCodeEquals(403);
    $member_role->grantPermission('delete any group_term:localgov_topic entity')->save();
    $this->container->get('cache.render')->invalidateAll();
    $this->drupalGet($term_url);
    $this->assertSession()->pageTextContains('Delete');
    $this->drupalGet($term_delete_url);
    $this->assertSession()->statusCodeEquals(200);
  }

}
