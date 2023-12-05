<?php

namespace Drupal\Tests\localgov_microsites_group\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\domain_group\Traits\GroupCreationTrait;
use Drupal\Tests\domain_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests directory facets in a group.
 *
 * @group localgov_microsites_group
 */
class MicrositeDirectoryFacetTest extends BrowserTestBase {

  use GroupCreationTrait;
  use InitializeGroupsTrait;
  use NodeCreationTrait;

  /**
   * Will be removed when issue #3204455 on Domain Site Settings gets merged.
   *
   * See https://www.drupal.org/project/domain_site_settings/issues/3204455.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   * phpcs:disable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_directories_db',
    'localgov_microsites_directories',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set base hostname.
    $this->setBaseHostname();

    // Create some microsites.
    $this->group1 = $this->createGroup([
      'label' => 'group-a1',
      'type' => 'microsite',
    ]);
    $this->group2 = $this->createGroup([
      'label' => 'group-a2',
      'type' => 'microsite',
    ]);
    $this->allTestGroups = [
      $this->group1,
      $this->group2,
    ];
    $this->initializeTestGroupsDomains();
    $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    $this->domain1 = $domain_storage->load('group_' . $this->group1->id());
    $this->domain2 = $domain_storage->load('group_' . $this->group2->id());

    // Create a user.
    $this->user = $this->drupalCreateUser();
    $this->group1->addMember($this->user, ['group_roles' => ['microsite-admin']]);
    $this->group2->addMember($this->user, ['group_roles' => ['microsite-admin']]);
  }

  /**
   * Test creating directory facets.
   */
  public function testMicrositeDirectoryFacetForms() {
    $facet_type = 'Test facet type';
    $facet_type_id = 'test_facet_type';
    $facet_name = $this->randomMachineName(12);

    // Login to site 1.
    $this->drupalGet($this->domain1->getUrl() . Url::fromRoute('user.login')->toString());
    $this->submitForm([
      'name' => $this->user->getAccountName(),
      'pass' => $this->user->passRaw,
    ], 'Log in');

    // Create facet type.
    $type_listing_url = Url::fromRoute('entity.group_relationship.group_localgov_directories_facet_type.list',
      [
        'group' => $this->group1->id(),
      ],
    )->toString();
    $this->drupalGet($this->domain1->getUrl() . $type_listing_url);
    $this->assertSession()->pageTextNotContains($facet_type);
    $type_add_url = Url::fromRoute('entity.group_relationship.group_localgov_directories_facet_type.add',
      [
        'group' => $this->group1->id(),
      ],
    )->toString();
    $this->drupalGet($this->domain1->getUrl() . $type_add_url);
    $this->submitForm([
      'edit-label' => $facet_type,
      'edit-id' => $facet_type_id,
    ], 'Save directory facets type');
    $this->assertSession()->addressEquals($type_listing_url);
    $this->assertSession()->pageTextContains($facet_type);

    // Create facet.
    $facet_listing_url = Url::fromRoute('view.lgms_group_directory_facets.page',
      [
        'group' => $this->group1->id(),
        'localgov_directories_facets_type' => $facet_type_id,
      ],
    )->toString();
    $this->drupalGet($this->domain1->getUrl() . $facet_listing_url);
    $this->assertSession()->pageTextContains('There are no directory facets yet.');
    $facet_add_url = Url::fromRoute('entity.group_relationship.group_localgov_directories_facets.add',
      [
        'group' => $this->group1->id(),
        'localgov_directories_facets_type' => $facet_type_id,
      ],
    )->toString();
    $this->drupalGet($this->domain1->getUrl() . $facet_add_url);
    $this->submitForm([
      'edit-title-0-value' => $facet_name ,
    ], 'Save');
    $this->assertSession()->addressEquals($facet_listing_url);
    $this->assertSession()->pageTextContains($facet_name);

    // Login to site 2.
    $this->drupalGet($this->domain2->getUrl() . Url::fromRoute('user.login')->toString());
    $this->submitForm([
      'name' => $this->user->getAccountName(),
      'pass' => $this->user->passRaw,
    ], 'Log in');

    // Check facet type is listed.
    $type_listing_url = Url::fromRoute('entity.group_relationship.group_localgov_directories_facet_type.list',
      [
        'group' => $this->group2->id(),
      ],
    )->toString();
    $this->drupalGet($this->domain2->getUrl() . $type_listing_url);
    $this->assertSession()->pageTextContains($facet_type);

    // Check facet isn't listed.
    $facet_listing_url = Url::fromRoute('view.lgms_group_directory_facets.page',
      [
        'group' => $this->group2->id(),
        'localgov_directories_facets_type' => $facet_type_id,
      ],
    )->toString();
    $this->drupalGet($this->domain2->getUrl() . $facet_listing_url);
    $this->assertSession()->pageTextContains('There are no directory facets yet.');
    $this->assertSession()->pageTextNotContains($facet_name);
  }

}
