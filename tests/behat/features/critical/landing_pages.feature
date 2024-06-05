@critical @listings

# Tests landing pages pages.
# Checks for links and expected text.
# Checking for form placeholder text is tricky, so we use HTML string
# matching based on the response HTML.
# We also introduce a custom step definition to check for instances of a thing,
# e.g. news articles in a listing.

Feature: Check structure of main landing/listing pages


  Scenario: Check News page
    Given I am on "/news"
    # There should be a grid of probably six things.
    Then I should see at least 3 instances of the element with selector ".newsroom__featured-news .lgd-teaser--localgov-news-article"
    # Check a likely tile heading or two.
    Then I should see text matching "News"
    Then I should see text matching "Love Essex is the official website of the Essex Waste Partnership "
    Then I should see text matching "Search news"
    Then I should see at least 1 instances of the element with selector ".views-exposed-form .form-text"
    Then I should see at least 2 instances of the element with selector ".facet-item"

  Scenario: Check Disposal Options page
    Given I am on "/disposal-options"
    Then I should see text matching "What do you want to get rid of?"
    Then I should see text matching "Essex Postcode"
    Then I should see at least 1 instances of the element with selector ".form-item-postcode"
    Then I should see at least 1 instances of the element with selector ".form-item-item"


