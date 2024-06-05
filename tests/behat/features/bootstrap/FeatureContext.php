<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext {

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

  /**
   * @Given I wait :seconds seconds
   */
  public function iWaitSeconds($seconds)
  {
    sleep($seconds);
  }

  /**
   * Click on the element with the provided xpath query
   *
   * @When /^I click on the element with xpath "([^"]*)"$/
   */
  public function iClickOnTheElementWithXPath($xpath)
  {
    $session = $this->getSession(); // get the mink session
    $element = $session->getPage()->find(
      'xpath',
      $session->getSelectorsHandler()->selectorToXpath('xpath', $xpath)
    ); // runs the actual query and returns the element

    // errors must not pass silently
    if (null === $element) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate XPath: "%s"', $xpath));
    }

    $element->click();
  }

  /**
   * Click on the element with the provided CSS Selector
   *
   * @When /^I click on the element with css selector "([^"]*)"$/
   */
  public function iClickOnTheElementWithCSSSelector($cssSelector)
  {
    $session = $this->getSession();
    $element = $session->getPage()->find(
      'xpath',
      $session->getSelectorsHandler()->selectorToXpath('css', $cssSelector) // just changed xpath to css
    );
    if (null === $element) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $cssSelector));
    }

    $element->click();
  }

  /**
   * @Then I should see iframe with title :title
   */
  public function iShouldSeeIframeWithTitle($iframeTitle)
  {
    $page = $this->getSession()->getPage();

    // Switch to the payment iframe.
    $iframe = $this->getSession()->getPage()->find('css', 'iframe[title="' . $iframeTitle . '"]');

    if (null === $iframe) {
      throw new \InvalidArgumentException(sprintf('Could not find iframe with title: "%s"', $iframeTitle));
    }
    $iframe_name = $iframe->getAttribute('name');

    // Switch Back to Main Window
    $this->getSession()->getDriver()->switchToIFrame(null);
  }


  /**
   * @When /^I print "(?P<message>(?:[^"]|\\")*)" to the console$/
   */
  public function iPrintToTheConsole($message)
  {
      print sprintf($message, $this->getSession()->getCurrentUrl());
  }

  /**
   * @When /^I print the HTML source to the console$/
   */
  public function iPrintTheHtmlSourceToTheConsole()
  {
      print $this->getSession()->getPage()->getContent();
  }

  /**
   * @Then /^I should see at least (?P<count>\d+) instances of the element with selector "(?P<selector>[^"]*)"$/
   */
  public function iShouldSeeAtLeastInstancesOfElement($count, $selector)
  {
    $elements = $this->getSession()->getPage()->findAll('css', $selector);

    if (count($elements) < $count) {
        throw new ExpectationException(sprintf('Expected at least %d instances of the element with selector "%s", but found %d.', $count, $selector, count($elements)), $this->getSession());
    }
  }
}
