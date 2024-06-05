@critical @listings

# Tests landing pages pages.
# Checks for links and expected text.
# Checking for form placeholder text is tricky, so we use HTML string
# matching based on the response HTML.
# We also introduce a custom step definition to check for instances of a thing,
# e.g. news articles in a listing.

Feature: Check structure of main landing/listing pages


  Scenario: Check Services page
    Given I am on "/services"
    # There should be a grid of probably six things.
    Then I should see at least 3 instances of the element with selector ".tiled-layout .tile"
    # Check a likely tile heading or two.
    Then I should see text matching "Health Information"
    Then I should see text matching "Reading"

  Scenario: Check Local Libraries page
    Given I am on "/local-libraries"
    Then I should see text matching "Join Your Library Service"
    Then I should see at least 2 instances of the element with selector ".tiled-layout .tile"


  Scenario: ELibrary page
    Given I am on "/elibrary"
    Then I should see text matching "eBooks"
    Then I should see text matching "eLearning Courses"
    Then I should see at least 3 instances of the element with selector ".tiled-layout .tile"
    # Check for an exposed filter for the map.
    Then the response should contain "Select Your Library Service"




