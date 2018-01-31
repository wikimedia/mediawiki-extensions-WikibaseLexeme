Then(/^Forms header should be there$/) do
  expect(on(LexemePage).forms_header?).to be true
end

Then(/^Forms container should be there$/) do
  expect(on(LexemePage).forms_container?).to be true
end

Then(/^I see at least one Form$/) do
  expect(on(LexemePage).forms.count).to be > 0
end

Given(/^for each Form there is an anchor equal to its ID$/) do
  on(LexemePage).forms.each do |form|
    id = form.id_element.when_visible.text
    anchor = form.anchor

    expect(anchor).to be == id
  end
end


Then(/^for each Form there is a representation and an ID$/) do
  on(LexemePage).forms.each do |form|
    expect(form.representations.count).to be > 0
    expect(form.id?).to be true
  end
end

Then(/^for each Form there is a statement list$/) do
  on(LexemePage).forms.each do |form|
    form.statements_element.when_visible
    expect(form.statements?).to be true
  end
end

Then(/^each representation has a language$/) do
  on(LexemePage).forms.each do |form|
    form.representations.each do |representation|
      expect(representation.language?).to be true
    end
  end
end

Given(/^for each Form there is a grammatical feature list$/) do
  on(LexemePage).forms.each do |form|
    expect(form.grammatical_feature_list?).to be true
  end
end

When(/^I click the Forms list add button$/) do
  on(LexemePage).add_lexeme_form_element.when_visible.click
  @form_I_am_currently_editing = on(LexemePage).forms[-1]
end

When(/^I enter "(.*?)" as the "(.*?)" form representation$/) do |representation, language|
  last_representation = @form_I_am_currently_editing.representations[-1]

  last_representation.value_input = representation
  last_representation.language_input = language
end

When(/^I save the Form$/) do
  # TODO: Had some problems here with element clickability, but failed to reproduce. Fix is probably needed
  @form_I_am_currently_editing.save_element.when_visible.click

  # Wait till the form is saved
  @form_I_am_currently_editing.edit_element.when_visible
end

Then(/^"(.*?)" should be displayed as the "(.*?)" representation of the Form$/) do |value, language|
  has_representation_with_value = on(LexemePage).forms.map { |f| f.representations.to_a }.to_a.flatten.any? do |representation|
    representation.value_element.when_visible.text == value
    representation.language_element.when_visible.text == language
  end

  expect(has_representation_with_value).to be true
end

When(/^I click on the add representation button$/) do
  @form_I_am_currently_editing.add_representation_element.when_visible.click
end

Given(/^I have a Lexeme with a Form$/) do
  step 'I have a Lexeme to test' # TODO: implement once Forms are storable

  # TODO: Creating the Form should be done on the backend once it is possible
  step 'I click the Forms list add button'
  step 'I enter "some representation" as the "en" form representation'
  step 'I save the Form'
end

When(/^I click on the first Form's edit button$/) do
  @form_I_am_currently_editing = on(LexemePage).forms[0]
  @form_I_am_currently_editing.edit_element.when_visible.click
end

When(/^I select the test item as the grammatical feature$/) do
  @form_I_am_currently_editing.grammatical_features_input_element.send_keys(@item_under_test['label'])
  @form_I_am_currently_editing.grammatical_feature_selection_first_option_element.when_visible.click
end

Then(/^I should see the item's label in the list of grammatical features of the first Form$/) do
  @first_form = on(LexemePage).forms[0]
  Watir::Wait.until { @first_form.grammatical_feature?(@item_under_test['label']) }

  expect(@first_form.grammatical_feature?(@item_under_test['label'])).to be true
end

When(/^I cancel the editing of the Form$/) do
  @form_I_am_currently_editing.cancel_element.when_visible.click
end

Then(/^I don't see the Form$/) do
  expect(@form_I_am_currently_editing.exists?).to be false
end

When(/^I click add statement on the Form$/) do
  @form_I_am_currently_editing.statement_group.add_statement_element.when_visible.click
  @statement_I_am_currently_editing = @form_I_am_currently_editing.statement_group.statements[-1]
end

When(/^I save the statement$/) do
  @statement_I_am_currently_editing.save_element.when_visible.click
  on(LexemePage).ajax_wait
end

Then(/^I see (.+?)=(.+?) statement in the Form statement list$/) do |handle, property_value|
  property_label = @properties[handle]['label']
  Watir::Wait.until(timeout = 5) do
    @form_I_am_currently_editing.statement_group.statement_with_value?(property_label, property_value)
  end

  expect(@form_I_am_currently_editing.statement_group.statement_with_value?(property_label, property_value)).to be true
end

Given(/^a grammatical feature exists for the first Form of the Lexeme$/) do
  # TODO: the grammatical feature to remove should be added in the backend once Forms can be stored
  step 'I have an item to test'
  step 'I click on the first Form\'s edit button'
  step 'I select the test item as the grammatical feature'
  step 'I save the Form'
end

When(/^I remove the first grammatical feature of the first Form$/) do
  gf_to_delete = on(LexemePage).forms[0].grammatical_features[0]
  @grammatical_feature_to_delete = gf_to_delete.text

  gf_to_delete.delete_button_element
      .when_visible
      .click
end

Then(/^the first Form should no longer have the removed grammatical feature$/) do
  expect(on(LexemePage).forms[0].grammatical_feature?(@grammatical_feature_to_delete)).to be false
end

Then(/^I add a Form$/) do
  step 'I click the Forms list add button'
  step 'I enter "some representation" as the "en" form representation'
  step 'I save the Form'
end

Then(/^I go to the history page$/) do
  @last_form_id_before_undo = on(LexemePage).forms[-1].id_element.when_visible.text
  on(LexemePage).view_history
end


Then(/^I undo the latest change$/) do
  # pick the latest revision and click "undo" link
  on(EntityHistoryPage).revisions[0].undo
  # confirm "undo"
  on(UndoPage).save_page
  # got back to the initial Lexeme page
end

Then(/^I restore the previous revision$/) do
  # pick the previous revision and click "restore" link
  on(EntityHistoryPage).revisions[1].restore
  # confirm "restore/undo"
  on(UndoPage).save_page
  # got back to the initial Lexeme page
end

Then(/^the new Form has the ID greater than the previous one$/) do
  last_form_id = on(LexemePage).forms[-1].id_element.when_visible.text

  expect(last_form_id).to be > @last_form_id_before_undo
end

