<?php

namespace Drupal\Tests\replicate_ui\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the UI functionality.
 *
 * @group replicate
 */
class ReplicateUITest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'replicate',
    'replicate_ui',
    'node',
    'views',
    'block',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->user = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
      'replicate entities',
      'administer users',
      'administer languages',
      'administer content translation',
      'create content translations',
      'translate any entity',
      'access content overview',
    ]);
    $node_type = NodeType::create([
      'type' => 'page',
    ]);
    $node_type->save();
    $this->node = Node::create([
      'title' => 'test title',
      'type' => 'page',
    ]);
    $this->node->save();

    $this->placeBlock('local_tasks_block');
    $this->placeBlock('system_messages_block');
    $this->config('replicate_ui.settings')
      ->set('entity_types', [
        'node',
        'user',
      ])
      ->save();
    \Drupal::service('router.builder')->rebuild();
    Cache::invalidateTags(['entity_types']);
  }

  /**
   * Basic functional test for the replicate functionality.
   */
  public function testFunctionality() {
    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->pageTextNotContains('Replicate');
    $this->drupalLogin($this->user);

    // Test the Replicate operation.
    $test_node = Node::create([
      'title' => 'Replicate operation',
      'type' => 'page',
    ]);
    $test_node->save();
    $this->drupalGet('admin/content');
    $this->getSession()->getPage()->fillField('title', 'Replicate operation');
    $this->getSession()->getPage()->pressButton('Filter');
    $this->getSession()->getPage()->clickLink('Replicate');
    $this->getSession()->getPage()->fillField('new_label_' . $test_node->language()->getId(), 'Replicate operation (Copy)');
    $this->getSession()->getPage()->pressButton('Replicate');
    $replicated_nodes = \Drupal::entityTypeManager()->getStorage('node')
      ->loadByProperties(['title' => 'Replicate operation (Copy)']);
    /** @var \Drupal\node\NodeInterface $replicated_node */
    $replicated_node = reset($replicated_nodes);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('(<em class="placeholder">' . $test_node->id() . '</em>) has been replicated to id <em class="placeholder">' . $replicated_node->id() . '</em>!');
    $this->assertSession()->addressEquals($replicated_node->toUrl());

    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->pageTextContains('Replicate');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/content', ['query' => ['title' => $this->node->getTitle()]]);
    $this->getSession()->getPage()->clickLink('Replicate');
    $this->assertSession()->statusCodeEquals(200);

    // Test the new label form element on the confirmation page.
    $this->assertSession()->pageTextContains('New label');
    $this->assertSession()->pageTextContains('This text will be used as the label of the new entity being created');
    $langcode = $this->node->language()->getId();
    $new_element_label = $this->assertSession()->elementExists('css', 'input[name="new_label_' . $langcode . '"]');
    $this->assertequals($this->node->label() . ' (Copy)', $new_element_label->getValue());
    $this->getSession()->getPage()->fillField('new_label_' . $langcode, 'Overriden replicate label');
    $this->getSession()->getPage()->pressButton('Replicate');
    $this->assertSession()->responseContains('<em class="placeholder">node</em> (<em class="placeholder">' . $this->node->id() . '</em>) has been replicated');
    $this->assertSession()->titleEquals('Overriden replicate label | Drupal');
    // Check that we don't show the new label element on entities without a
    // label key.
    $this->drupalGet("/user/{$this->user->id()}/replicate");
    $this->getSession()->getPage()->clickLink('Replicate');
    $this->assertSession()->pageTextContains('Are you sure you want to replicate user entity id');
    $this->assertSession()->elementNotExists('css', 'input[name="new_label"]');
    $this->assertSession()->pageTextNotContains('New label');
    $this->assertSession()->pageTextNotContains('This text will be used as the label of the new entity being created');
  }

  /**
   * Test replicating on multi-lingual environments.
   */
  public function testMultilingual() {
    $this->drupalLogin($this->user);

    foreach (['es', 'fr'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    // Enable translation for page nodes.
    $this->drupalGet('/admin/config/regional/content-language');
    $edit = [
      'entity_types[node]' => TRUE,
      'settings[node][page][fields][title]' => TRUE,
      'settings[node][page][translatable]' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');

    $node = Node::create([
      'type' => 'page',
      'title' => 'Multilingual test',
    ]);
    $node->save();
    $node->addTranslation('es', [
      'title' => 'Spanish title',
    ]);
    $node->addTranslation('fr', [
      'title' => 'French title',
    ]);
    $node->save();

    $this->drupalGet($node->toUrl());
    $this->getSession()->getPage()->clickLink('Replicate');

    $test_sets = [
      'en' => $node,
      'es' => $node->getTranslation('es'),
      'fr' => $node->getTranslation('fr'),
    ];
    /** @var \Drupal\node\NodeInterface $translation */
    foreach ($test_sets as $langcode => $translation) {
      $new_element_label = $this->assertSession()->elementExists('css', 'input[name="new_label_' . $langcode . '"]');
      $this->assertequals($translation->label() . ' (Copy)', $new_element_label->getValue());
      $this->getSession()->getPage()->fillField('new_label_' . $langcode, 'Overriden replicate label - ' . $langcode);
    }
    $this->getSession()->getPage()->pressButton('Replicate');
    $this->assertSession()->responseContains('<em class="placeholder">node</em> (<em class="placeholder">' . $node->id() . '</em>) has been replicated');
    $this->assertSession()->titleEquals('Overriden replicate label - en | Drupal');

    // Check the replicated translations have the correct labels as well.
    $replicated = $this->getNodeByTitle('Overriden replicate label - en');
    $replicated_es = $replicated->getTranslation('es');
    $this->assertEquals('Overriden replicate label - es', $replicated_es->label());
    $replicated_fr = $replicated->getTranslation('fr');
    $this->assertEquals('Overriden replicate label - fr', $replicated_fr->label());
  }

}
