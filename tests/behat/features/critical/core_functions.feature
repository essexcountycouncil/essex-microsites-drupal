@api

Feature: Test core functionality.

  # Make one of each kind of node.

  Scenario: Create many nodes
    Given "page" content:
    | title    |
    | Page one |
    | Page two |
    And "childrens_book_review" content:
    | title                        | field_county | field_book_title | field_book_author | created |
    | First childrens_book_review  | Carlow       | Winnie The Pooh | A A Milne | 2024-12-31 |
    | Second childrens_book_review | Wicklow      | The House At Pooh Corner | A A Milne | 2024-12-31 |
    And "homepage" content:
    | title          |
    | First homepage  |
    | Second homepage |
    And "landing_page" content:
    | title          |
    | First landing_page  |
    | Second landing_page |
    And "local_authority_service" content:
    | title          |
    | First local_authority_service  |
    | Second local_authority_service |
    And "news" content:
    | title          |
    | First news  |
    | Second news |
    # Pay attention to the data format here: dates are a compound field.
    And "event" content:
    | title           | field_event_date |
    | My Event Title  | value: 2024-12-31 - end_value: 2025-01-02 |
    And I am logged in as a user with the "administrator" role
    When I go to "admin/content"
    Then I should see "Page one"
    And I should see "Page two"
    And I should see "Review from Carlow for Winnie The Pooh, A A Milne"
    And I should see "Review from Wicklow for The House At Pooh Corner, A A Milne"
    And I should see "First homepage"
    And I should see "Second homepage"
    And I should see "First landing_page"
    And I should see "Second landing_page"
    And I should see "First news"
    And I should see "Second news"
    And I should see "First local_authority_service"
    And I should see "Second local_authority_service"
    And I should see "My Event Title"

  Scenario: Run cron
    Given I am logged in as a user with the "administrator" role
    When I run cron
    And am on "admin/reports/dblog"
    Then I should see the link "Cron run completed"

