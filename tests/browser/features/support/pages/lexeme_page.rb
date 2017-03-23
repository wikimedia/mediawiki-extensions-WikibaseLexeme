class LexemePage
  include PageObject
  include EntityPage

  span(:forms_header, id: 'forms')

  def create_lexeme(lexeme_data)
    wb_api = MediawikiApi::Wikidata::WikidataClient.new URL.repo_api
    resp = wb_api.create_entity(lexeme_data, "lexeme")

    id = resp['entity']['id']
    url = URL.repo_url(ENV['LEXEME_NAMESPACE'] + id)
    { 'url' => url }
  end
end
