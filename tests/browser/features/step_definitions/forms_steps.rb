Then(/^Forms header should be there$/) do
  expect(on(LexemePage).forms_header?).to be true
end

Then(/^Forms container should be there$/) do
  expect(on(LexemePage).forms_container?).to be true
end

Then(/^I see at least one Form$/) do
  expect(on(LexemePage).forms.count).to be > 0
end


Then(/^for each Form there is a representation and an ID$/) do
  #todo: this only checks if there is at least one id and representation
  expect(on(LexemePage).form_representation?).to be true
  expect(on(LexemePage).form_id?).to be true
end

Then(/^for each Form there is a statement list$/) do
  on(LexemePage).forms.each do |form|
    expect(form.statements?).to be true
  end
end

Then(/^each representation is enclosed in tag having lang attribute with "(.+)" as a value$/) do  |value|
  #todo: this only checks if there is at least one lang attribute
 on(LexemePage).form_representation_element.attribute('lang').should == value
end

Given(/^for each Form there is a grammatical feature list$/) do
  on(LexemePage).forms.each do |form|
    expect(form.grammatical_features?).to be true
  end
end

When(/^I click the Forms list add button$/) do
  on(LexemePage).add_lexeme_form_element.when_visible.click
end

When(/^I enter "(.+)" as the form representation$/) do |representation|
  on(LexemePage) do |page|
    page.lexeme_form_input_field_element.when_visible.clear
    page.lexeme_form_input_field = representation
  end
end

When(/^I save the new Form$/) do
  on(LexemePage).lexeme_new_form_save_element.when_visible.click
end

Then(/^"(.+)" should be displayed as a representation in the list of Forms$/) do |representation|
  has_lexeme_form = on(LexemePage).lexeme_form_representation_text_elements
    .any? { |element| element.text == representation }

  expect(has_lexeme_form).to be true
end

Given(/^I have a Lexeme with a Form$/) do
  step 'I have a Lexeme to test' # TODO: implement once Forms are storable
end

When(/^I click on first Form's edit button$/) do
  on(LexemePage).lexeme_first_form_edit_element.when_visible.click
end

When(/^I select "(.*?)" as the grammatical feature$/) do |feature|
  on(LexemePage) do |page|
    page.grammatical_feature_input_element.send_keys(feature)
    page.grammatical_feature_selection_first_option_element.when_visible.click
  end
end

When(/^I save the first Form$/) do
  on(LexemePage).lexeme_first_form_save_element.when_visible.click
end

Then(/^I should see "(.*?)" in the list of grammatical features$/) do |feature|
  Watir::Wait.until(timeout = 5) do
    on(LexemePage).first_form_grammatical_features_element.text.include? feature
  end

end
