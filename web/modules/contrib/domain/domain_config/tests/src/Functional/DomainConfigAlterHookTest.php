<?php

namespace Drupal\Tests\domain_config\Functional;

/**
 * Tests for https://www.drupal.org/node/2896434#comment-12265088.
 *
 * @group domain_config
 */
class DomainConfigAlterHookTest extends DomainConfigTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'domain',
    'domain_config',
    'domain_config_test',
    'domain_config_middleware_test',
  ];

  /**
   * Domain id key.
   *
   * @var string
   */
  public $key = 'example_com';

  /**
   * The domain negotiator service.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  public $negotiator;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * Test setup.
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a domain.
    $this->domainCreateTestDomains();

    // Get the services.
    $this->negotiator = \Drupal::service('domain.negotiator');
    $this->moduleHandler = \Drupal::service('module_handler');
  }

  /**
   * Tests domain request alteration.
   */
  public function testHookDomainRequestAlter() {
    // Check for the count of hook implementations.
    // This varies from Drupal 9.4 onward.
    if (method_exists($this->moduleHandler, 'getImplementations')) {
      // @phpstan-ignore-next-line
      $hooks = $this->moduleHandler->getImplementations('domain_request_alter');
      $this->assertCount(1, $hooks, 'One hook implementation found.');
    }
    elseif (method_exists($this->moduleHandler, 'hasImplementations')) {
      $hooks = $this->moduleHandler->hasImplementations('domain_request_alter');
      $this->assertTrue($hooks, 'Hook implementations found.');
    }

    // Assert that the hook is also called on a request with a HTTP Middleware
    // that requests config thus triggering an early hook invocation (before
    // modules are loaded by the kernel).
    $this->drupalGet('<front>');
    $this->assertEquals('invoked', $this->getSession()->getResponseHeader('X-Domain-Config-Test-page-attachments-hook'));
  }

}
