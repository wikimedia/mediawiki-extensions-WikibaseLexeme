@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Forms of a Lexeme

  Background:
    Given I am on a Lexeme page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

  @integration
  Scenario: Basic Forms section
    Then Forms header should be there
     And Forms container should be there
     And for each Form there is a representation and an ID
     And each representation is enclosed in tag having lang attribute with "some language" as a value

  @integration
  Scenario: View Forms grammatical features
    And for each Form there is a grammatical feature list

  @integration
  Scenario: Add grammatical feature
    Given I have a Lexeme with a Form
     And I am on the page of the Lexeme to test
     And I have an item to test
    When I click on the first Form's edit button
     And I select the test item as the grammatical feature
     And I save the Form
    Then I should see the item's label in the list of grammatical features of the Form

  @integration
  Scenario: Remove grammatical feature
    Given I have a Lexeme with a Form
     And I am on the page of the Lexeme to test
     And a grammatical feature exists for the first Form of the Lexeme
    When I click on the first Form's edit button
     And I remove the first grammatical feature of the first Form
    Then the first Form should no longer have the removed grammatical feature

  @integration
  Scenario: Change representation
    Given I have a Lexeme with a Form
     And I am on the page of the Lexeme to test
    When I click on the first Form's edit button
     And I enter "new-representation" as the form representation
     And I save the Form
    Then "new-representation" should be displayed as a representation of the Form

  @integration
  Scenario: Add Form
    When I am on a Lexeme page
     And I click the Forms list add button
     And I enter "whatever" as the form representation
     And I save the Form
    Then "whatever" should be displayed as a representation of the Form


  @integration
  Scenario: Cancel Form addition
    Given I am on a Lexeme page
      And I click the Forms list add button
     When I cancel the editing of the Form
     Then I don't see the Form

  @integration
  Scenario: Edit statements on new Form
    Given I have the following properties with datatype:
      | stringprop | string |
      And I am on a Lexeme page
      And I click the Forms list add button
      And I enter "newForm" as the form representation
      And I save the Form
     When I click add statement on the Form
      And I select the claim property stringprop
      And I enter somestring in the claim value input field
      And I save the statement
     Then I see stringprop=somestring statement in the Form statement list


  @integration
  Scenario: I can see each Form's statements
    Then I see at least one Form
    And for each Form there is a statement list
