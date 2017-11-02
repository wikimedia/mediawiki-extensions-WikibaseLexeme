When(/^I click the lexeme header edit button$/) do
  on(LexemePage).lexeme_header.edit_element.when_visible.click
  on(LexemePage).ajax_wait
end

When(/^I enter the test item id into the lexeme language field$/) do
  on(LexemePage).lexeme_header.lexeme_language_input = @item_under_test['label']
  on(LexemePage).ajax_wait
end

When(/^I click the lexeme header save button$/) do
  on(LexemePage).lexeme_header.save_element.when_visible.click
  on(LexemePage).ajax_wait
end

Then(/^I should see the item in the lexeme language field$/) do
  expect(
    on(LexemePage).lexeme_header.lexeme_language_element.text
  ).to eq @item_under_test['label']
end

When(/^I enter the test item id into the lexical category field$/) do
  on(LexemePage).lexeme_header.lexical_category_input = @item_under_test['label']
  on(LexemePage).ajax_wait
end

Then(/^I should see the item in the lexical category field$/) do
  expect(
    on(LexemePage).lexeme_header.lexical_category_element.text
  ).to eq @item_under_test['label']
end
