@catalyst @javascript @local @local_assessfreq
Feature: Assessment frequency

  Background:
    Given the following "courses" exist:
      | shortname | fullname |
      | C1        | Course 1 |
    And the following "activities" exist:
      | activity | course | name               | duedate                   |
      | assign   | C1     | Test assignment 1  | ## January 1st, 2022 ##   |
      | assign   | C1     | Test assignment 2  | ## February 1st, 2022 ##  |
      | assign   | C1     | Test assignment 3  | ## March 1st, 2022 ##     |
      | assign   | C1     | Test assignment 4  | ## April 1st, 2022 ##     |
      | assign   | C1     | Test assignment 5  | ## May 1st, 2022 ##       |
      | assign   | C1     | Test assignment 6  | ## June 1st, 2022 ##      |
      | assign   | C1     | Test assignment 7  | ## July 1st, 2022 ##      |
      | assign   | C1     | Test assignment 8  | ## August 1st, 2022 ##    |
      | assign   | C1     | Test assignment 9  | ## September 1st, 2022 ## |
      | assign   | C1     | Test assignment 10 | ## October 1st, 2022 ##   |
    And the following "activities" exist:
      | activity | course | name           | timeclose                 |
      | choice   | C1     | Test choice 1  | ## January 1st, 2022 ##   |
      | choice   | C1     | Test choice 2  | ## February 1st, 2022 ##  |
      | choice   | C1     | Test choice 3  | ## March 1st, 2022 ##     |
      | choice   | C1     | Test choice 4  | ## April 1st, 2022 ##     |
      | choice   | C1     | Test choice 5  | ## May 1st, 2022 ##       |
      | choice   | C1     | Test choice 6  | ## June 1st, 2022 ##      |
      | choice   | C1     | Test choice 7  | ## July 1st, 2022 ##      |
      | choice   | C1     | Test choice 8  | ## August 1st, 2022 ##    |
      | choice   | C1     | Test choice 9  | ## September 1st, 2022 ## |
    And the following "activities" exist:
      | activity | course | name             | timeavailableto          |
      | data     | C1     | Test database 1  | ## January 1st, 2022 ##  |
      | data     | C1     | Test database 2  | ## February 1st, 2022 ## |
      | data     | C1     | Test database 3  | ## March 1st, 2022 ##    |
      | data     | C1     | Test database 4  | ## April 1st, 2022 ##    |
      | data     | C1     | Test database 5  | ## May 1st, 2022 ##      |
      | data     | C1     | Test database 6  | ## June 1st, 2022 ##     |
      | data     | C1     | Test database 7  | ## July 1st, 2022 ##     |
      | data     | C1     | Test database 8  | ## August 1st, 2022 ##   |
    And the following "activities" exist:
      | activity | course | name             | timeclose                |
      | feedback | C1     | Test feedback 1  | ## January 1st, 2022 ##  |
      | feedback | C1     | Test feedback 2  | ## February 1st, 2022 ## |
      | feedback | C1     | Test feedback 3  | ## March 1st, 2022 ##    |
      | feedback | C1     | Test feedback 4  | ## April 1st, 2022 ##    |
      | feedback | C1     | Test feedback 5  | ## May 1st, 2022 ##      |
      | feedback | C1     | Test feedback 6  | ## June 1st, 2022 ##     |
      | feedback | C1     | Test feedback 7  | ## July 1st, 2022 ##     |
    And the following "activities" exist:
      | activity | course | name         | duedate                  |
      | forum    | C1     | Test forum 1 | ## January 1st, 2022 ##  |
      | forum    | C1     | Test forum 2 | ## February 1st, 2022 ## |
      | forum    | C1     | Test forum 3 | ## March 1st, 2022 ##    |
      | forum    | C1     | Test forum 4 | ## April 1st, 2022 ##    |
      | forum    | C1     | Test forum 5 | ## May 1st, 2022 ##      |
      | forum    | C1     | Test forum 6 | ## June 1st, 2022 ##     |
    And the following "activities" exist:
      | activity | course | name          | deadline                 |
      | lesson   | C1     | Test lesson 1 | ## January 1st, 2022 ##  |
      | lesson   | C1     | Test lesson 2 | ## February 1st, 2022 ## |
      | lesson   | C1     | Test lesson 3 | ## March 1st, 2022 ##    |
      | lesson   | C1     | Test lesson 4 | ## April 1st, 2022 ##    |
      | lesson   | C1     | Test lesson 5 | ## May 1st, 2022 ##      |
    And the following "activities" exist:
      | activity | course | name        | timeclose                |
      | quiz     | C1     | Test quiz 1 | ## January 1st, 2022 ##  |
      | quiz     | C1     | Test quiz 2 | ## February 1st, 2022 ## |
      | quiz     | C1     | Test quiz 3 | ## March 1st, 2022 ##    |
      | quiz     | C1     | Test quiz 4 | ## April 1st, 2022 ##    |
    And the following "activities" exist:
      | activity | course | name         | timeclose                |
      | scorm    | C1     | Test scorm 1 | ## January 1st, 2022 ##  |
      | scorm    | C1     | Test scorm 2 | ## February 1st, 2022 ## |
      | scorm    | C1     | Test scorm 3 | ## March 1st, 2022 ##    |
    And the following "activities" exist:
      | activity | course | name            | submissionend            |
      | workshop | C1     | Test workshop 1 | ## January 1st, 2022 ##  |
      | workshop | C1     | Test workshop 2 | ## February 1st, 2022 ## |
    And the following config values are set as admin:
      | config  | value                                                        | plugin           |
      | modules | assign,choice,data,feedback,forum,lesson,quiz,scorm,workshop | local_assessfreq |

  Scenario: Basic test of dashboard display
    Given I log in as "admin"
    When I navigate to "Plugins > Local plugins > Assessment Frequency > Clear history" in site administration
    And I press "Reprocess all events"
    And I press "Continue"
    And I run all adhoc tasks
    And I navigate to "Reports > Assessment reports > Assessment dashboard" in site administration
    And I click on "Select year" "button" in the "local-assessfreq-report-heatmap" "region"
    And I click on "2022" "link" in the "local-assessfreq-heatmap-year" "region"
    And I click on "td[data-date='2022-1-1']" "css_element"
    Then the following should exist in the "Title" table:
     | Assignment: Test assignment 1 |
     | Choice: Test choice 1         |
     | Database: Test database 1     |
     | Feedback: Test feedback 1     |
     | Forum: Test forum 1           |
     | Lesson: Test lesson 1         |
     | Quiz: Test quiz 1             |
     | SCORM package: test scorm 1   |
     | Workshop: Test workshop 1     |
