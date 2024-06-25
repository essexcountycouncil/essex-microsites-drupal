<?php

namespace Drupal\Tests\localgov_microsites_events\Functional;

use Drupal\localgov_microsites_group\DomainFromGroupTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\localgov_microsites_group\Traits\GroupCreationTrait;
use Drupal\Tests\localgov_microsites_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests access to event view and search.
 *
 * @group localgov_microsites_events
 */
class MicrositeEventViewAccesTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
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
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'localgov_microsites_events',
  ];

  /**
   * The group permissions helper.
   *
   * @var \Drupal\localgov_microsites_group\ContentTypeHelperInterface
   */
  protected $contentTypeHelper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->contentTypeHelper = $this->container->get('localgov_microsites_group.content_type_helper');

    // Create a microsite.
    $this->group = $this->createGroup([
      'label' => 'group-a1',
      'type' => 'microsite',
    ]);
    $this->allTestGroups = [
      $this->group,
    ];
    $this->createMicrositeGroupsDomains([$this->group]);
    $this->domain = $this->getDomainFromGroup($this->group);
  }

  /**
   * Test events view.
   */
  public function testMicrositeEventsViewAccess() {

    $this->contentTypeHelper->moduleEnable('localgov_microsites_events', $this->group);
    drupal_flush_all_caches();
    $this->drupalGet($this->domain->getUrl() . 'events');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($this->domain->getUrl() . 'events/search');
    $this->assertSession()->statusCodeEquals(200);

    $this->contentTypeHelper->moduleDisable('localgov_microsites_events', $this->group);
    $this->entityTypeManager->getStorage('group')->resetCache();
    drupal_flush_all_caches();
    $this->drupalGet($this->domain->getUrl() . 'events');
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet($this->domain->getUrl() . 'events/search');
    $this->assertSession()->statusCodeEquals(404);
  }

}
