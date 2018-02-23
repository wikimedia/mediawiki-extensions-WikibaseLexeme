@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Senses of a Lexeme

  Background:
    Given I am on a Lexeme page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

  # TODO: add @integration tag once senses work again
  Scenario: Basic senses section
    Given there is a Sense to test
    Then Senses header should be there
     And Senses container should be there
     And I see at least one Sense
     And for each Sense there is a gloss and an ID

  # TODO: add @integration tag once statements work again
  Scenario: I can see statements of each Sense
    Then I see at least one Sense
    And for each Sense there is a statement list

  # TODO: add @integration tag once senses work again
  Scenario: Link to Sense
    Given there is a Sense to test
    Then for each Sense there is an anchor equal to its ID

  # TODO: add @integration tag once senses work again
  Scenario: Adding Gloss
    Given there is a Sense to test
    When I click the first Sense's edit button
     And I add a Gloss for "ru" language with value "Просто коза"
     And I save the Sense
    # TODO refresh page
    Then I should see Gloss with value "Просто коза" for "ru" language

  # TODO: add @integration tag once senses work again
  Scenario: Changing Glosses
    Given there is a Sense to test
    When I click the first Sense's edit button
     And I change the text of the first Gloss definition
     And I save the Sense
    # TODO refresh page
    Then I should see the new text as the Gloss definition

  # TODO: add @integration tag once senses work again
  Scenario: Removing Gloss
    Given there is a Sense to test
    When I click the first Sense's edit button
     And I remove the first Gloss definition
     And I save the Sense
    # TODO refresh page
    Then I don't see that Gloss definition

  # TODO: add @integration tag once senses work again
  Scenario: Adding Senses
    When I click on the Senses list add button
     And I add a Gloss for "en" language with value "Whatever"
     And I save the Sense
     # TODO refresh page
    Then I should see a new Sense with that Gloss
