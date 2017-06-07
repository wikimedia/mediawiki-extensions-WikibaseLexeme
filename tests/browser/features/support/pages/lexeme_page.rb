
class Statement
  include PageObject

  div(:property_label, class: 'wikibase-statementgroupview-property-label')
  div(:value, css: '.wikibase-statementview-mainsnak .wikibase-snakview-value')
  a(:save, css: '.wikibase-toolbar-button-save a' )
end

class StatementGroup
  include PageObject

  page_sections(:statements, Statement, css: '.wikibase-statementgroupview.listview-item')
  a(:add_statement, css: '.wikibase-statementgrouplistview > .wikibase-addtoolbar-container a')

  def statement_with_value?(property_label, value)
    self.statements.any? do |statement|
      statement.property_label_element.text == property_label && statement.value_element.text == value
    end
  end
end


class LexemeForm
  include PageObject

  span(:representation, class: 'wikibase-lexeme-form-text')
  div(:grammatical_feature_list, class: 'wikibase-lexeme-form-grammatical-features')
  as(:grammatical_features, css: '.wikibase-lexeme-form-grammatical-features-values > span > a')
  div(:statements, class: 'wikibase-statementgrouplistview')
  textarea(:representation_input, css: '.wikibase-lexeme-form-text > textarea')
  text_field(:grammatical_features_input, css: '.wikibase-lexeme-form-grammatical-features-values input')
  a(:save, css: '.wikibase-toolbar-button-save > a')
  a(:cancel, css: '.wikibase-toolbar-button-cancel > a')
  a(:edit, css: '.wikibase-toolbar-button-edit > a')
  a(:grammatical_feature_selection_first_option, css: '.wikibase-lexeme-form-grammatical-features-values .oo-ui-menuOptionWidget:first-of-type a')

  page_section(:statement_group, StatementGroup, class: 'wikibase-statementgrouplistview')

  def grammatical_feature?(label)
    self.grammatical_features_element.select do |gf_element|
      gf_element.text == label
    end.count > 0
  end
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

  def create_lexeme(lexeme_data)
    wb_api = MediawikiApi::Wikidata::WikidataClient.new URL.repo_api
    resp = wb_api.create_entity(lexeme_data, "lexeme")

    id = resp['entity']['id']
    url = URL.repo_url(ENV['LEXEME_NAMESPACE'] + id)
    { 'url' => url }
  end
end
