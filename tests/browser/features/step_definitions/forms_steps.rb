Then(/^Forms header should be there$/) do
  expect(on(LexemePage).forms_header?).to be true
end

Then(/^Forms container should be there$/) do
  expect(on(LexemePage).forms_container?).to be true
end

Then(/^for each Form there is a representation and an ID$/) do
  #todo: this only checks if there is at least one id and representation
  expect(on(LexemePage).form_representation?).to be true
  expect(on(LexemePage).form_id?).to be true
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
