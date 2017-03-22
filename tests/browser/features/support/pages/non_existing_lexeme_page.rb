class NonExistingLexemePage
  include PageObject
  page_url ENV['WIKIDATA_REPO_URL'] + ENV['LEXEME_NAMESPACE'] + 'L-invalid'

  h1(:first_heading, class: 'firstHeading')
  div(:no_article_text, class: 'noarticletext')
end
