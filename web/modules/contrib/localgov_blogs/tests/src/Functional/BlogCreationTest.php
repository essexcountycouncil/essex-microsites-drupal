<?php

namespace Drupal\Tests\localgov_blogs\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests LocalGov Blogs creation.
 *
 * @group localgov_page
 */
class BlogCreationTest extends BrowserTestBase {

  use NodeCreationTrait;

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
    'localgov_blogs',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin_user);
  }

  /**
   * Test the blog post create form.
   */
  public function testBlogChannelSelection() {

    // Check no blog channel message.
    $this->drupalGet('/node/add/localgov_blog_post');
    $this->assertSession()->pageTextContains('Warning message');
    $this->assertSession()->pageTextContains('There are no blogs channels.');
    $this->assertSession()->responseContains('name="localgov_blog_channel"');

    // Check blog channel automatic selection when one channel.
    $this->drupalGet('/node/add/localgov_blog_channel');
    $channel = $this->drupalCreateNode([
      'type' => 'localgov_blog_channel',
      'title' => 'Channel 1',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->drupalGet('/node/add/localgov_blog_post');
    $this->assertSession()->responseNotContains('name="localgov_blog_channel"');
    $this->submitForm([
      'title[0][value]' => 'Blog post 1',
      'body[0][summary]' => 'Blog post 1 summary',
      'body[0][value]' => 'Blog post 1 body',
    ], 'Save');
    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('title', 'Blog post 1')
      ->execute();
    $node_storage = $node = \Drupal::entityTypeManager()->getStorage('node');
    $post = $node_storage->load(reset($nids));
    $this->assertSame($channel->id(), $post->get('localgov_blog_channel')->target_id);

    // Check channel select for multiple channels.
    $this->drupalCreateNode([
      'type' => 'localgov_blog_channel',
      'title' => 'Channel 2',
      'status' => NodeInterface::PUBLISHED,
    ]);
    $this->drupalGet('/node/add/localgov_blog_post');
    $this->assertSession()->responseContains('name="localgov_blog_channel"');
    $this->assertSession()->responseContains('Channel 1</option>');
    $this->assertSession()->responseContains('Channel 2</option>');

    // Check promote on channel field.
    $this->drupalGet('/node/' . $post->id() . '/edit');
    $this->submitForm([
      'localgov_blog_channel_promote' => 1,
    ], 'Save');
    $channel = $node_storage->load($channel->id());
    $featured_posts = [
      ['target_id' => $post->id()],
    ];
    $this->assertSame($featured_posts, $channel->get('localgov_blog_channel_featured')->getValue());

    // Check unpromote.
    $this->drupalGet('/node/' . $post->id() . '/edit');
    $this->submitForm([
      'localgov_blog_channel_promote' => 0,
    ], 'Save');
    $node_storage->resetCache([$channel->id()]);
    $channel = $node_storage->load($channel->id());
    $this->assertEmpty($channel->get('localgov_blog_channel_featured')->getValue());
  }

}
