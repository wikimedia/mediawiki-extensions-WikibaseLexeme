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
    When I click on first Form's edit button
     And I select the test item as the grammatical feature
     And I save the Form
    Then I should see the item's label in the list of grammatical features of the Form

  @integration
  Scenario: Change representation
    Given I have a Lexeme with a Form
     And I am on the page of the Lexeme to test
    When I click on first Form's edit button
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
  Scenario: I can see each Form's statements
    Then I see at least one Form
    And for each Form there is a statement list
