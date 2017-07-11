
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

class GrammaticalFeatureValue
  include PageObject

  a(:value)
  a(:delete_button, css: '.oo-ui-buttonElement > .oo-ui-buttonElement-button')
end

class FormRepresentation
  include PageObject

  text_field(:value_input, class: 'representation-widget_representation-value-input')
  text_field(:language_input, class: 'representation-widget_representation-language-input')
  span(:value, class: 'representation-widget_representation-value')
  span(:language, class: 'representation-widget_representation-language')
end

class LexemeForm
  include PageObject

  div(:id, class: 'wikibase-lexeme-form-id')
  div(:grammatical_feature_list, class: 'wikibase-lexeme-form-grammatical-features')
  div(:statements, class: 'wikibase-statementgrouplistview')
  text_field(:grammatical_features_input, css: '.wikibase-lexeme-form-grammatical-features-values input')
  a(:save, css: '.wikibase-toolbar-button-save > a')
  a(:cancel, css: '.wikibase-toolbar-button-cancel > a')
  a(:edit, css: '.wikibase-toolbar-button-edit > a')
  a(:grammatical_feature_selection_first_option, css: '.wikibase-lexeme-form-grammatical-features-values .oo-ui-menuOptionWidget:first-of-type a')
  button(:add_representation, class: 'representation-widget_add')

  page_section(:statement_group, StatementGroup, class: 'wikibase-statementgrouplistview')
  page_sections(
    :grammatical_features,
    GrammaticalFeatureValue,
    css: '.wikibase-lexeme-form-grammatical-features-values > span, .wikibase-lexeme-form-grammatical-features-values .oo-ui-tagItemWidget'
  )
  page_sections(
    :representations,
    FormRepresentation,
    css: '.representation-widget_representation, .representation-widget_representation-edit-box'
  )

  def anchor
    @root_element.attribute('id')
  end

  def grammatical_feature?(label)
    self.grammatical_features.any? do |gf|
      gf.value_element.when_present.text == label
    end
  end
end


class GlossDefinition
  include PageObject

  span(:language, css: '.wikibase-lexeme-sense-gloss-language > span')
  text_field(:language_input, class: 'wikibase-lexeme-sense-gloss-language-input')
  span(:value, class: 'wikibase-lexeme-sense-gloss-value')
  text_field(:value_input, class: 'wikibase-lexeme-sense-gloss-value-input')
  button(:remove, css:".wikibase-lexeme-sense-glosses-remove")
end

class Sense
  include PageObject

  a(:edit, css: '.wikibase-toolbar-button-edit > a')
  a(:save, css: '.wikibase-toolbar-button-save > a')
  button(:add_gloss, css: '.wikibase-lexeme-sense-glosses-add')

  span(:id, class: 'wikibase-lexeme-sense-glosses-sense-id')
  div(:statements, class: 'wikibase-statementgrouplistview')

  page_sections(:glosses, GlossDefinition, class: 'wikibase-lexeme-sense-gloss')

  def anchor
    @root_element.attribute('id')
  end

  def gloss?(language_code, value)
    self.glosses.any? do |g|
      g.value_element.when_present.text == value &&
          g.language_element.when_present.text == language_code
    end
  end
end


class LexemePage
  include PageObject
  include EntityPage

  span(:forms_header, id: 'forms')
  div(:forms_container, class: 'wikibase-lexeme-forms')
  h3(:form_representation, class: 'wikibase-lexeme-form-representation')
  span(:senses_header, id: 'senses')
  div(:senses_container, class: 'wikibase-lexeme-senses')

  page_sections(:forms, LexemeForm, class: 'wikibase-lexeme-form')
  page_sections(:senses, Sense, class: 'wikibase-lexeme-sense')

  a(:add_lexeme_form, css: '.wikibase-lexeme-forms-section > .wikibase-addtoolbar-container a')
  a(:add_sense, css: '.wikibase-lexeme-senses-section > .wikibase-addtoolbar-container a')

  def create_lexeme(lexeme_data)
    wb_api = MediawikiApi::Wikidata::WikidataClient.new URL.repo_api
    resp = wb_api.create_entity(lexeme_data, "lexeme")

    id = resp['entity']['id']
    url = URL.repo_url(ENV['LEXEME_NAMESPACE'] + id)
    { 'url' => url }
  end
end
