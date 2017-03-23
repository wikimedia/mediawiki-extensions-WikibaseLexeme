@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Non existing Lexeme

@integration
Scenario: Non-existing Lexeme
  Given I am on a page of a non existing Lexeme
  Then check if the page says the entity does not exist
