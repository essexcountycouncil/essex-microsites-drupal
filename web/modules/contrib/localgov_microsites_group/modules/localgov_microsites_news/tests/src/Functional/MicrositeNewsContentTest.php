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
 * Tests directory content in a group.
 *
 * @group localgov_microsites_group
 */
class MicrositeNewsContentTest extends BrowserTestBase {

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
    'localgov_microsites_news',
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

    // Create some directory content.
    $this->newsroom1 = $this->createNewsroom($this->groups[1]);
    $this->article1 = $this->createNewsArticles($this->newsroom1, $this->groups[1], 2);
    $this->newsroom2 = $this->createNewsroom($this->groups[2]);
    $this->article2 = $this->createNewsArticles($this->newsroom2, $this->groups[2], 2);

    // Index directory content.
    $index = Index::load('localgov_news');
    $index->indexItems();
  }

  /**
   * Test content appears on the correct site.
   */
  public function testMicrositeNewsContent() {

    // Check content appears on the correct sites.
    $this->drupalGet($this->domain1->getUrl() . $this->newsroom1->toUrl()->toString());
    $this->assertSession()->pageTextContains($this->article1[0]->label());
    $this->assertSession()->pageTextContains($this->article1[1]->label());
    $this->assertSession()->pageTextNotContains($this->article2[0]->label());
    $this->assertSession()->pageTextNotContains($this->article2[1]->label());

    $this->drupalGet($this->domain2->getUrl() . $this->newsroom2->toUrl()->toString());
    $this->assertSession()->pageTextContains($this->article2[0]->label());
    $this->assertSession()->pageTextContains($this->article2[1]->label());
    $this->assertSession()->pageTextNotContains($this->article1[0]->label());
    $this->assertSession()->pageTextNotContains($this->article1[1]->label());
  }

  /**
   * Test directories search.
   */
  public function testMicrositeNewsSearch() {

    // Search site 1.
    $options = [
      'query' => [
        'search_api_fulltext' => $this->article1[0]->label(),
      ],
    ];
    $this->drupalGet($this->domain1->getUrl() . 'news/search', $options);
    $this->assertSession()->pageTextContains($this->article1[0]->label());
    $this->assertSession()->pageTextNotContains($this->article1[1]->label());
    $this->assertSession()->pageTextNotContains($this->article2[0]->label());
    $this->assertSession()->pageTextNotContains($this->article2[1]->label());

    // Search site 2.
    $options = [
      'query' => [
        'search_api_fulltext' => $this->article2[0]->label(),
      ],
    ];
    $this->drupalGet($this->domain2->getUrl() . 'news/search', $options);
    $this->assertSession()->pageTextContains($this->article2[0]->label());
    $this->assertSession()->pageTextNotContains($this->article2[1]->label());
    $this->assertSession()->pageTextNotContains($this->article1[0]->label());
    $this->assertSession()->pageTextNotContains($this->article1[1]->label());
  }

  /**
   * Create newsroom in group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group to create newsroom in.
   *
   * @return \Drupal\node\NodeInterface
   *   The newsroom.
   */
  protected function createNewsroom(GroupInterface $group) {

    $directory = $this->createNode([
      'type' => 'localgov_newsroom',
      'title' => $this->randomMachineName(12),
      'status' => NodeInterface::PUBLISHED,
    ]);
    $directory->save();
    $group->addRelationship($directory, 'group_node:localgov_newsroom');

    return $directory;
  }

  /**
   * Create count news articles in newsroom and group.
   *
   * @param \Drupal\node\NodeInterface $channel
   *   Newsroom to create articles in.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group to create article in.
   * @param int $count
   *   Number of news articles to create.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Array of news articles.
   */
  protected function createNewsArticles(NodeInterface $channel, GroupInterface $group, int $count) {
    $articles = [];

    for ($i = 0; $i < $count; $i++) {
      $article = $this->createNode([
        'type' => 'localgov_news_article',
        'title' => $this->randomMachineName(12),
        'localgov_newsroom' => [
          'target_id' => $channel->id(),
        ],
        'status' => NodeInterface::PUBLISHED,
      ]);
      $article->save();
      $group->addRelationship($article, 'group_node:localgov_news_article');
      $articles[] = $article;
    }

    return $articles;
  }

}
