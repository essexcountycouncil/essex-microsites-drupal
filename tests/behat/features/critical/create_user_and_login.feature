@critical
@api

Feature: Create a user with a specific role and test login

  Scenario: Create user with specific role and login
    # Create a user with the API to play with.
    # The user will be destroyed again afterwards.
    Given users:
    | name     | mail            | status | password | role |
    | joeuser2 | joe2@example.com | 1      | password | editor |
    # Log in as an admin to check our user exists.
    When I am logged in as a user with the "administrator" role
    When I visit "/admin/people"
    Then I should see the link "joeuser2"
    # Log out.
    And I am not logged in
    # Try logging in with the user login form, with our user's creds.
    And I am on "/user/login"
    And I fill in "joeuser2" for "Username" in the "content" region
    And I fill in "password" for "Password" in the "content" region
    And I press "Log in" in the "content" region
    And I wait 2 seconds
 #   Then I should see the heading "Member for"
    Then the response should contain "Log out"



