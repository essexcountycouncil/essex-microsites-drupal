@api

Feature: Test core functionality.

  # Make one of each kind of node.

  Scenario: Create many nodes
    Given "localgov_page" content:
    | title    |
    | Page one |
    | Page two |
    And "localgov_directory" content:
    | title          |
    | First localgov_directory  |
    | Second localgov_directory |
    And "localgov_directories_venue" content:
    | title          |
    | First localgov_directories_venue  |
    | Second localgov_directories_venue |
    And "localgov_news_article" content:
    | title          |
    | First news  |
    | Second news |
    # Pay attention to the data format here: dates are a compound field.
    #And "event" content:
    #| title           | field_event_date |
    #| My Event Title  | value: 2024-12-31 - end_value: 2025-01-02 |
    # Need to figure out logged in status: there's no log out link to be found
    # by Behat yet. However, once nothing dies before this point we can be
    # fairly confident that core node creation will work.

    #And I am logged in as a user with the "microsites_trusted_editor" role
    #When I go to "admin/content"
    #Then I should see "Page one"
    #And I should see "Page two"
    #And I should see "First localgov_directory"
    #And I should see "Second localgov_directory"
    #And I should see "First news"
    #And I should see "Second news"
    #And I should see "First localgov_directories_venue"
    #And I should see "Second localgov_directories_venue"

  #Scenario: Run cron
   # When I run cron
    #Then I should see the link "Cron run completed"

