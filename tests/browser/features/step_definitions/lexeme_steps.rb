Given(/^Anonymous edit warnings are disabled$/) do
  on(LexemePage).set_noanonymouseditwarning_cookie
end

Given(/^The copyright warning has been dismissed$/) do
  on(LexemePage).set_copyright_ack_cookie
end

Given(/^I am on a Lexeme page$/) do
  step 'I have a Lexeme to test'
  step 'I am on the page of the Lexeme to test'
end

Given(/^I have a Lexeme to test$/) do
  lexeme_data = '{"lexicalCategory":"Q1","language":"Q1"}'
  @lexeme_under_test = visit(LexemePage).create_lexeme(lexeme_data)
end

Given(/^I am on the page of the Lexeme to test$/) do
  on(LexemePage).navigate_to_entity @lexeme_under_test['url']
end
