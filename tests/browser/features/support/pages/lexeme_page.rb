class LexemeForm
  include PageObject

  div(:grammatical_features, class: 'wikibase-lexeme-form-grammatical-features')
end


class LexemePage
  include PageObject
  include EntityPage

  span(:forms_header, id: 'forms')
  div(:forms_container, class: 'wikibase-lexeme-forms')
  h3(:form_representation, class: 'wikibase-lexeme-form-representation')
  span(:form_id, class: 'wikibase-lexeme-form-id')
  span(:senses_header, id: 'senses')
  div(:senses_container, class: 'wikibase-lexeme-senses')

  page_sections(:forms, LexemeForm, class: 'wikibase-lexeme-form')

  # Lexeme Form
  a(:add_lexeme_form, css: '.wikibase-lexeme-forms-section > .wikibase-addtoolbar-container a')
  textarea(:lexeme_form_input_field, css: '.wikibase-lexemeformview:last-of-type .wikibase-lexeme-form-text > textarea')
  spans(:lexeme_form_representation_text, css: '.wikibase-lexeme-form-representation .wikibase-lexeme-form-text')
  a(:lexeme_form_save, css: '.wikibase-lexemeformview:last-of-type .wikibase-toolbar-button-save > a')

  def create_lexeme(lexeme_data)
    wb_api = MediawikiApi::Wikidata::WikidataClient.new URL.repo_api
    resp = wb_api.create_entity(lexeme_data, "lexeme")

    id = resp['entity']['id']
    url = URL.repo_url(ENV['LEXEME_NAMESPACE'] + id)
    { 'url' => url }
  end
end
