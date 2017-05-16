require 'mediawiki_selenium/cucumber'
require 'mediawiki_selenium'
require 'mediawiki_api/wikidata'
require 'require_all'

lenv = MediawikiSelenium::Environment.load_default
ENV['WIKIDATA_REPO_URL'] = lenv.lookup(:mediawiki_url)
ENV['WIKIDATA_REPO_API'] = lenv.lookup(:mediawiki_url_api, default: lambda do
  lenv.lookup(:mediawiki_url)
      .gsub(%r{wiki/$}, 'w/api.php')
      .gsub(%r{index.php/?$}, 'api.php')
end)
ENV['LEXEME_NAMESPACE'] = 'Lexeme:'
ENV['ITEM_NAMESPACE'] = 'Item:'
ENV['LANGUAGE_CODE'] = lenv.lookup(:language_code, default: -> { 'en' })

require_all File.dirname(__FILE__) + '/../../../../../Wikibase/tests/browser/features/support/modules'
require_all File.dirname(__FILE__) + '/../../../../../Wikibase/tests/browser/features/step_definitions'
require_all File.dirname(__FILE__) + '/pages'
require File.dirname(__FILE__) + '/../../../../../Wikibase/tests/browser/features/support/pages/item_page'
