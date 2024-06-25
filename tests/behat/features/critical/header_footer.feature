@critical

# This feature is all about testing the header and footer.
# We check that links are present, and we check that expected text is present.
# We're using regions as defined in behat.yml.
# Then we click on top level pages to ensure they don't error.

Feature: Check header and footer for expected links

  Scenario: Check header items
    Given I am on the homepage
    Then I should see the link "About Love Essex" in the "header" region
    Then I should see the link "Visit a recycling centre" in the "header" region
    Then I should see the link "News" in the "header" region
    Then I should see the link "Ideas" in the "header" region
    When I click "About Love Essex"
    Then I should be on "/about-love-essex"
    When I click "Visit a recycling centre"
    Then I should be on "/find-recycling-centre"
    When I click "News"
    Then I should be on "/news"
    When I click "Ideas"
    Then I should be on "/ideas"

  Scenario: Check footer items
    Given I am on the homepage
    Then I should see the text "Together we can" in the "footer" region
    Then I should see the text "Essex County Council" in the "footer" region
    Then I should see the link "Contact us" in the "footer" region
    Then I should see the link "Cookies" in the "footer" region
    Then I should see the link "Accessibility" in the "footer" region
    Then I should see the link "Privacy and data protection" in the "footer" region
    Then I should see the link "Terms and conditions" in the "footer" region
    Then I should see the link "Modern slavery and human trafficking statement" in the "footer" region
    When I click "Contact us"
    Then I should be on "/contact-us"
    When I click "Privacy and data protection"
    Then I should be on "https://www.essex.gov.uk/about-essexgovuk/privacy-and-data-protection"
    Given I am on the homepage
    When I click "Accessibility"
    Then I should be on "http://love-essex.eccmicro.al.anner.ie/accessibility"
    Given I am on the homepage
    When I click "Terms and conditions"
    Then I should be on "https://www.essex.gov.uk/about-essexgovuk/terms-and-conditions"
    Given I am on the homepage
    When I click "Modern slavery and human trafficking statement"
    Then I should be on "https://www.essex.gov.uk/business/modern-slavery-and-human-trafficking-statement"



