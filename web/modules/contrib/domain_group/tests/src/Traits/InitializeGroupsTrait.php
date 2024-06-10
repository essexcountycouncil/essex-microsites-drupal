<?php

namespace Drupal\Tests\domain_group\Traits;

use Drupal\Tests\domain\Traits\DomainTestTrait;

/**
 * Provides group types and group entities for use in test classes.
 *
 * This trait provides protected members to store 2 group types ('a' and 'b')
 * and then 2 groups of each type. Calling initializeTestGroups() will
 * initialize everything and populate all the protected member variables.
 */
trait InitializeGroupsTrait {

  use DomainTestTrait;

  /**
   * A dummy group type with ID 'a'.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeA;

  /**
   * A dummy group type with ID 'b'.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeB;

  /**
   * Test group A1, of type 'a'.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupA1;

  /**
   * Test group A2, of type 'a'.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupA2;

  /**
   * Test group A3, of type 'a'.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupA3;

  /**
   * Test group B1, of type 'b'.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupB1;

  /**
   * Test group B2, of type 'b'.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupB2;

  /**
   * Test group B3, of type 'b'.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $groupB3;

  /**
   * All created groups.
   *
   * @var array
   */
  protected $allTestGroups;

  /**
   * All test domains.
   *
   * @var array
   */
  protected $domains;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Sets a base hostname for running tests.
   *
   * @var string
   */
  public $baseHostname;

  /**
   * Test node for group A1.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nodeA1;

  /**
   * Test node for group A2.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nodeA2;

  /**
   * Test node for group A3.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nodeA3;

  /**
   * Initializes all the test group types and groups.
   */
  protected function initializeTestGroups($config1 = [], $config2 = [], $config3 = []) {
    // Setting base hostname.
    $this->setBaseHostname();
    // Create the group types.
    $this->groupTypeA = $this->createGroupType([
      'id' => 'a',
      'label' => 'Type A',
    ]);
    $this->groupTypeB = $this->createGroupType([
      'id' => 'b',
      'label' => 'Type B',
    ]);

    // Create the groups.
    $this->groupA1 = $this->createGroup(['label' => 'group-A1', 'type' => 'a'] + $config1);
    $this->groupA2 = $this->createGroup(['label' => 'group-A2', 'type' => 'a'] + $config2);
    $this->groupA3 = $this->createGroup(['label' => 'group-A3', 'type' => 'a'] + $config3);
    $this->groupB1 = $this->createGroup(['label' => 'group-B1', 'type' => 'b'] + $config1);
    $this->groupB2 = $this->createGroup(['label' => 'group-B2', 'type' => 'b'] + $config2);
    $this->groupB3 = $this->createGroup(['label' => 'group-B3', 'type' => 'b'] + $config3);

    // Creating and array with all.
    $this->allTestGroups = [
      $this->groupA1,
      $this->groupA2,
      $this->groupA3,
      $this->groupB1,
      $this->groupB2,
      $this->groupB3,
    ];
  }

  /**
   * Initializes all the domains for the test group.
   */
  protected function initializeTestGroupsDomains() {
    // Creating domains.
    $this->domains = [''];
    /** @var \Drupal\group\Entity\GroupInterface $group */
    foreach ($this->allTestGroups as $group) {
      $this->domains[] = [
        'subdomain' => strtolower($group->label()),
        'id' => 'group_' . $group->id(),
        'name' => $group->label(),
        'third_party_settings' => [
          'domain_group' => ['group' => $group->id()],
        ],
      ];
    }
    $this->domainCreateTestDomains($this->domains, 7);

    // Creating domain site settings.
    foreach ($this->allTestGroups as $group) {
      $domain_id = 'group_' . $group->id();
      $config_id = 'domain.config.' . $domain_id . '.system.site';
      $config = $this->getConfigFactory()->getEditable($config_id);
      $config->set('name', $group->label());
      $config->set('slogan', $group->label() . ' Slogan');
      $config->set('mail', 'group-' . $group->id() . '@user.com');
      $config->set('page.front', '/group/' . $group->id());
      $config->set('page.403', '/denied');
      $config->set('page.404', '/not-found');
    }
    $config->save();
  }

  /**
   * Initializes test group content.
   */
  protected function initializeTestGroupRelationship() {
    // Creating node type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => t('Article')]);

    // Enabling article nodes to be assigned to only 'A' group type.
    $this->entityTypeManager->getStorage('group_relationship_type')
      ->createFromPlugin($this->groupTypeA, 'group_node:article')->save();

    // Creating nodes.
    $this->nodeA1 = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Group A1 content',
    ]);
    $this->nodeA2 = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Group A2 content',
    ]);
    $this->nodeA3 = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Group A3 content',
    ]);
    // Adding nodes to groups.
    $this->groupA1->addRelationship($this->nodeA1, 'group_node:article');
    $this->groupA2->addRelationship($this->nodeA2, 'group_node:article');
    $this->groupA3->addRelationship($this->nodeA3, 'group_node:article');

    // Enabling article nodes to be assigned to only 'B' group type.
    $this->entityTypeManager->getStorage('group_relationship_type')
      ->createFromPlugin($this->groupTypeB, 'group_node:article')->save();

    // Creating nodes.
    $this->nodeB1 = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Group B1 content',
    ]);
    $this->nodeB2 = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Group B2 content',
    ]);
    $this->nodeB3 = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Group B3 content',
    ]);
    // Adding nodes to groups.
    $this->groupB1->addRelationship($this->nodeB1, 'group_node:article');
    $this->groupB2->addRelationship($this->nodeB2, 'group_node:article');
    $this->groupB3->addRelationship($this->nodeB3, 'group_node:article');
  }

  /**
   * Returns the config factory to use.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  protected function getConfigFactory() {
    if (empty($this->configFactory)) {
      $this->configFactory = \Drupal::service('config.factory');
    }
    return $this->configFactory;
  }

  /**
   * Generates a list of domains for testing.
   *
   * The script may also add test1, test2, test3 up to any number to test a
   * large number of domains.
   *
   * @param array $list
   *   An optional list of subdomains to apply instead of the default set.
   * @param int $count
   *   The number of domains to create.
   * @param string|null $base_hostname
   *   The root domain to use for domain creation (e.g. example.com). You should
   *   normally leave this blank to account for alternate test environments.
   */
  public function domainCreateTestDomains(array $list, $count = 1, $base_hostname = NULL) {
    if (empty($base_hostname)) {
      $base_hostname = $this->baseHostname;
    }
    for ($i = 0; $i < $count; $i++) {
      if ($i === 0) {
        $hostname = $base_hostname;
        $machine_name = 'example.com';
        $name = 'Example';
      }
      elseif (!empty($list[$i])) {
        $hostname = $list[$i]['subdomain'] . '.' . $base_hostname;
        $machine_name = $list[$i]['id'];
        $name = $list[$i]['name'];
      }
      // These domains are not setup and are just for UX testing.
      else {
        $hostname = 'test' . $i . '.' . $base_hostname;
        $machine_name = 'test' . $i . '.example.com';
        $name = 'Test ' . $i;
      }
      // Create a new domain programmatically.
      $values = [
        'hostname' => $hostname,
        'name' => $name,
        'id' => \Drupal::entityTypeManager()->getStorage('domain')->createMachineName($machine_name),
      ];
      if (!empty($list[$i]['third_party_settings'])) {
        $values['third_party_settings'] = $list[$i]['third_party_settings'];
      }
      $domain = \Drupal::entityTypeManager()->getStorage('domain')->create($values);
      $domain->save();
    }
  }

  /**
   * Sets the unique group access config.
   *
   * @param bool $value
   *   The new value.
   */
  public function setUniqueGroupAccess($value = TRUE) {
    $config = $this->getConfigFactory()->getEditable('domain_group.settings');
    $config->set('unique_group_access', $value);
    $config->save();
    node_access_rebuild();
  }

}
