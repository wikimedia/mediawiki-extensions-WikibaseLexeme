Then(/^Forms header should be there$/) do
  expect(on(LexemePage).forms_header?).to be true
end
