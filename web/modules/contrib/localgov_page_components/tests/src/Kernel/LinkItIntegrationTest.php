<?php

namespace Drupal\Tests\localgov_page_components\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs_library\Entity\LibraryItem;

/**
 * LinkIt integration tests for Link and Contact Page components.
 *
 * @group localgov_page_components
 */
class LinkItIntegrationTest extends KernelTestBase {

  /**
   * LinkIt profile.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $defaultLinkItProfile;

  /**
   * UUID value assigned to our custom Matcher.
   *
   * @var string
   */
  protected $pageComponentMatcherUuid = '';

  /**
   * LinkIt Suggestion Manager.
   *
   * @var \Drupal\linkit\SuggestionManager
   */
  protected $suggestionManager;

  /**
   * Our LinkIt substitution plugin instance.
   *
   * @var \Drupal\linkit\SubstitutionInterface
   */
  protected $substitutionPlugin;

  /**
   * Integrates Page components with LinkIt.
   */
  public function setUp(): void {
    parent::setUp();

    $this->container->get('module_installer')->install([
      'user',
      'node',
      'linkit',
    ]);
    $this->container->get('module_installer')->install([
      'localgov_paragraphs',
      'localgov_page_components',
    ]);

    // Integrate Page components with LinkIt.
    $this->defaultLinkItProfile = $this->container->get('entity_type.manager')->getStorage('linkit_profile')->load('default');
    $this->suggestionManager = $this->container->get('linkit.suggestion_manager');
    $this->substitutionPlugin = $this->container->get('plugin.manager.linkit.substitution')->createInstance('paragraphs_library_item_localgovdrupal');
  }

  /**
   * Test for searching Link and Contact Page components.
   *
   * Creates a localgov_link, a localgov_contact, and a localgov_image Page
   * components and then searches those using our custom LinkIt matcher plugin.
   */
  public function testLinkItSearch() {

    $this->addLinkAndContactPageComponents();

    // First, search for a Link.
    $linkit_suggestions = $this->suggestionManager->getSuggestions($this->defaultLinkItProfile, 'Foo')->getSuggestions();
    $linkit_suggestions_count = count($linkit_suggestions);

    $expected_linkit_suggestions_count = 1;
    $this->assertEquals($linkit_suggestions_count, $expected_linkit_suggestions_count);

    // Next, search for a Contact.
    $linkit_suggestions = $this->suggestionManager->getSuggestions($this->defaultLinkItProfile, 'Baz')->getSuggestions();
    $linkit_suggestions_count = count($linkit_suggestions);

    $expected_linkit_suggestions_count = 1;
    $this->assertEquals($linkit_suggestions_count, $expected_linkit_suggestions_count);

    // Lastly, search for both Link and Contact.
    $linkit_suggestions = $this->suggestionManager->getSuggestions($this->defaultLinkItProfile, 'Test Paragraph')->getSuggestions();
    $linkit_suggestions_count = count($linkit_suggestions);

    $expected_linkit_suggestions_count = 2;
    $this->assertEquals($linkit_suggestions_count, $expected_linkit_suggestions_count);
  }

  /**
   * Test for substituting page components with their URLs.
   *
   * For localgov_link and localgov_contact page components, substitution will
   * result in a URL where the URL is sourced from a field value.
   *
   * For localgov_image page component, substitution will produce the page
   * component's own URL.
   */
  public function testLinkItSubstitution() {

    $page_components = $this->addLinkAndContactPageComponents();

    $link_url = $this->substitutionPlugin->getUrl($page_components['localgov_link'])->toString();
    $this->assertEquals($link_url, 'https://example.net/foo');

    $contact_url = $this->substitutionPlugin->getUrl($page_components['localgov_contact'])->toString();
    $this->assertEquals($contact_url, 'https://example.net/bar');

    $img_url = $this->substitutionPlugin->getUrl($page_components['localgov_image'])->toString();
    $img_page_component_url = $page_components['localgov_image']->toUrl('canonical')->toString();
    $this->assertEquals($img_url, $img_page_component_url);
  }

  /**
   * Creates necessary test data for search and replacement.
   *
   * Creates three page components:
   * - localgov_link
   * - localgov_contact
   * - localgov_image.
   */
  protected function addLinkAndContactPageComponents(): array {

    $link_para = Paragraph::create([
      'title'        => 'Test Paragraph Foo bar',
      'type'         => 'localgov_link',
      'localgov_url' => 'https://example.net/foo',
    ]);
    $link_para->save();
    $link_page_component = LibraryItem::create([
      'label' => 'Link: Test Paragraph Foo bar',
      'paragraphs' => $link_para,
    ]);
    $link_page_component->save();

    $contact_para = Paragraph::create([
      'title'                => 'Test Paragraph Baz qux',
      'type'                 => 'localgov_contact',
      'localgov_contact_url' => 'https://example.net/bar',
    ]);
    $contact_para->save();
    $contact_page_component = LibraryItem::create([
      'label' => 'Contact: Test Paragraph Baz qux',
      'paragraphs' => $contact_para,
    ]);
    $contact_page_component->save();

    $img_para = Paragraph::create([
      'title' => 'Test Paragraph image',
      'type'  => 'localgov_image',
    ]);
    $img_para->save();
    $img_page_component = LibraryItem::create([
      'label'      => 'Image: Test Paragraph image',
      'paragraphs' => $img_para,
    ]);
    $img_page_component->save();

    return [
      'localgov_link'    => $link_page_component,
      'localgov_contact' => $contact_page_component,
      'localgov_image'   => $img_page_component,
    ];
  }

}
