@critical

# This feature is all about testing the header and footer.
# We check that links are present, and we check that expected text is present.
# We're using regions as defined in behat.yml.
# Then we click on top level pages to ensure they don't error.

Feature: Check header and footer for expected links

  Scenario: Check header items
    Given I am on the homepage
    Then I should see the link "Join" in the "header" region
    Then I should see the link "My Account" in the "header" region
    Then I should see the link "English" in the "header" region
    Then I should see the link "Gaeilge" in the "header" region
    When I click "Gaeilge"
    Then I should be on "/ga"
    When I click "English"
    Then I should be on "/"

  Scenario: Check footer items
    Given I am on the homepage
    Then I should see the text "Website Info" in the "footer" region
    Then I should see the text "Shared Library Catalogue" in the "footer" region
    Then I should see the text "Public Libraries" in the "footer" region


  Scenario: Follow top links
    Given I am on the homepage
    When I click "Join"
    Then I should be on "/join-your-library"
    When I click "My Account"
    Then I should be on "https://librariesireland.spydus.ie/cgi-bin/spydus.exe/MSGTRN/WPAC/LOGINB"
    Given I am on the homepage
    When I click "Join now!"
    Then I should be on "/join-your-library"
    When I click "eLibrary"
    Then I should be on "/elibrary"
    When I click "Services"
    Then I should be on "/services"
