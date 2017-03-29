Then(/^Senses header should be there$/) do
  expect(on(LexemePage).senses_header?).to be true
end

Then(/^Senses container should be there$/) do
  expect(on(LexemePage).senses_container?).to be true
end
