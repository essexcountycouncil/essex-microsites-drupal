<?php

namespace Drupal\Tests\localgov_microsites_group\Functional;

use Drupal\group\Entity\GroupInterface;
use Drupal\localgov_microsites_group\DomainFromGroupTrait;
use Drupal\node\NodeInterface;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\localgov_microsites_group\Traits\GroupCreationTrait;
use Drupal\Tests\localgov_microsites_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests sitewide search.
 *
 * @group localgov_microsites_group
 */
class MicrositeSitewideSearchTest extends BrowserTestBase {

  use InitializeGroupsTrait;
  use NodeCreationTrait;
  use GroupCreationTrait, DomainFromGroupTrait {
    GroupCreationTrait::getEntityTypeManager insteadof DomainFromGroupTrait;
  }

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
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_microsites_group',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createMicrositeGroups([], 2);
    $this->createMicrositeGroupsDomains($this->groups);
    $this->domain1 = $this->getDomainFromGroup($this->groups[1]);
    $this->domain2 = $this->getDomainFromGroup($this->groups[2]);

    // Create some content.
    $this->pages1 = [
      $this->createPage($this->groups[1]),
      $this->createPage($this->groups[1]),
    ];
    $this->pages2 = [$this->createPage($this->groups[2])];

    // Index directory content.
    $index = Index::load('localgov_sitewide_search');
    $index->indexItems();
  }

  /**
   * Test sitewide search.
   */
  public function testMicrositeSitewideSearch() {

    // Search site 1.
    $options = [
      'query' => [
        's' => $this->pages1[0]->label(),
      ],
    ];
    $this->drupalGet($this->domain1->getUrl() . 'search', $options);
    $this->assertSession()->pageTextContains($this->pages1[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[1]->label());
    $this->assertSession()->pageTextNotContains($this->pages2[0]->label());

    // Search site 2.
    $options = [
      'query' => [
        's' => $this->pages2[0]->label(),
      ],
    ];
    $this->drupalGet($this->domain2->getUrl() . 'search', $options);
    $this->assertSession()->pageTextContains($this->pages2[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[0]->label());
    $this->assertSession()->pageTextNotContains($this->pages1[1]->label());
  }

  /**
   * Create page in group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group to create directory in.
   *
   * @return \Drupal\node\NodeInterface
   *   The directory channel.
   */
  protected function createPage(GroupInterface $group) {

    $page = $this->createNode([
      'type' => 'localgov_page',
      'title' => $this->randomMachineName(12),
      'status' => NodeInterface::PUBLISHED,
    ]);
    $page->save();
    $group->addRelationship($page, 'group_node:localgov_page');

    return $page;
  }

}
