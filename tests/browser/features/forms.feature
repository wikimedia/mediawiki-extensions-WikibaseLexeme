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
     And I have an item with label "Plural" and description "Plural"
    When I click on first Form's edit button
     And I select "Plural" as the grammatical feature
     And I save the first Form
    Then I should see "Plural" in the list of grammatical features

  @integration
  Scenario: Add Form
    When I am on a Lexeme page
     And I click the Forms list add button
     And I enter "whatever" as the form representation
     And I save the new Form
    Then "whatever" should be displayed as a representation in the list of Forms

  @integration
  Scenario: I can see each Form's statements
    Then I see at least one Form
    And for each Form there is a statement list
