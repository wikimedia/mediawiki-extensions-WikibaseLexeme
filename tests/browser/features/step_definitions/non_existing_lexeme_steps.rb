Given(/^I am on a page of a non existing Lexeme$/) do
  visit_page(NonExistingLexemePage)
end

Then(/^check if the page says the entity does not exist$/) do
  on_page(NonExistingLexemePage) do |page|
    expect(page.first_heading?).to be true
    expect(page.first_heading_element.text).to be == ENV['LEXEME_NAMESPACE'] + 'L-invalid'
    expect(page.no_article_text?).to be true
  end
end
