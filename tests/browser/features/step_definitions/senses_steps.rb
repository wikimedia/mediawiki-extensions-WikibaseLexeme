Then(/^Senses header should be there$/) do
  expect(on(LexemePage).senses_header?).to be true
end

Then(/^Senses container should be there$/) do
  expect(on(LexemePage).senses_container?).to be true
end

Then(/^I see at least one Sense$/) do
  expect(on(LexemePage).senses.count).to be > 0
end

Then(/^for each Sense there is a gloss and an ID$/) do
  on(LexemePage).senses.each do |sense|
     expect(sense.sense_gloss?).to be true
     expect(sense.sense_id?).to be true
  end
end