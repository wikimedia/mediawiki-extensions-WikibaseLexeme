Given(/^I am on a Lexeme page$/) do
  step 'I have a Lexeme to test'
  step 'I am on the page of the Lexeme to test'
end

Given(/^I have a Lexeme to test$/) do
  step 'I have an item to test'
  lexeme_data = '{"lexicalCategory":"' + @item_under_test['id'] + '","language":"' + @item_under_test['id'] + '","lemmas":{"en":{"language":"en", "value":"test"}}}'
  @lexeme_under_test = visit(LexemePage).create_lexeme(lexeme_data)
end

Given(/^I am on the page of the Lexeme to test$/) do
  on(LexemePage).navigate_to_entity @lexeme_under_test['url']
end

When(/^I reload the lexeme page$/) do
  browser.refresh
  on(LexemePage).wait_for_load
end
