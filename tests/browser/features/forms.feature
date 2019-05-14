@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Forms of a Lexeme

  Background:
    Given I am on a Lexeme page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled
      And VisualEditor welcome message is disabled

  @integration
  Scenario: Link to Form
    And for each Form there is an anchor equal to its ID

  @integration
  Scenario: Remove grammatical feature
    Given I have a Lexeme with a Form
     And a grammatical feature exists for the first Form of the Lexeme
    When I click on the first Form's edit button
     And I remove the first grammatical feature of the first Form
     And I save the Form
     And I reload the lexeme page
    Then the first Form should no longer have the removed grammatical feature

  @integration
  Scenario: Change multi-variant representations
    Given I have a Lexeme with a Form
    When I click on the first Form's edit button
     And I enter "colors" as the "en-ca" form representation
     And I click on the add representation button
     And I enter "colours" as the "en-gb" form representation
     And I save the Form
     And I reload the lexeme page
    Then "colors" should be displayed as the "en-ca" representation of the Form
     And "colours" should be displayed as the "en-gb" representation of the Form

  @integration
  Scenario: Edit statements on new Form
    Given I have the following properties with datatype:
      | stringprop | string |
      And I click the Forms list add button
      And I enter "newForm" as the "en" form representation
      And I save the Form
     When I click add statement on the Form
      And I select the claim property stringprop
      And I enter somestring in the claim value input field
      And I save the statement
     Then I see stringprop=somestring statement in the Form statement list

  @integration
  Scenario: FormId counter is not decremented when old revision is restored
    Given I am on a Lexeme page
      And I add a Form
     Then I go to the history page
      And I restore the previous revision
     When I add a Form
     Then the new Form has the ID greater than the previous one
