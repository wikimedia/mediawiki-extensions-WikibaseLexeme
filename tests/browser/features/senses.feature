@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Senses of a Lexeme

  Background:
    Given I am on a Lexeme page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

  @integration
  Scenario: Basic senses section
    Then Senses header should be there
     And Senses container should be there
     And I see at least one Sense
     And for each Sense there is a gloss and an ID

  @integration
  Scenario: I can see statements of each Sense
    Then I see at least one Sense
    And for each Sense there is a statement list