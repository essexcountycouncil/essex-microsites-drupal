<?php

namespace Drupal\Tests\localgov_microsites_events\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\domain_group\Traits\GroupCreationTrait;
use Drupal\Tests\domain_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests access to event view and search.
 *
 * @group localgov_microsites_events
 */
class MicrositeEventViewAccesTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
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
    'localgov_microsites_events',
  ];

  /**
   * The group permissions helper.
   *
   * @var \Drupal\localgov_microsites_group\GroupPermissionsHelperInterface
   */
  protected $groupPermissionsHelper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->groupPermissionsHelper = $this->container->get('localgov_microsites_group.permissions_helper');

    // Create a microsite.
    $this->setBaseHostname();
    $this->group = $this->createGroup([
      'label' => 'group-a1',
      'type' => 'microsite',
    ]);
    $this->allTestGroups = [
      $this->group,
    ];
    $this->initializeTestGroupsDomains();
    $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    $this->domain = $domain_storage->load('group_' . $this->group->id());
  }

  /**
   * Test events view.
   */
  public function testMicrositeEventsViewAccess() {

    $this->groupPermissionsHelper->moduleEnable('localgov_microsites_events', $this->group);
    drupal_flush_all_caches();
    $this->drupalGet($this->domain->getUrl() . 'events');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($this->domain->getUrl() . 'events/search');
    $this->assertSession()->statusCodeEquals(200);

    $this->groupPermissionsHelper->moduleDisable('localgov_microsites_events', $this->group);
    drupal_flush_all_caches();
    $this->drupalGet($this->domain->getUrl() . 'events');
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet($this->domain->getUrl() . 'events/search');
    $this->assertSession()->statusCodeEquals(404);
  }

}
