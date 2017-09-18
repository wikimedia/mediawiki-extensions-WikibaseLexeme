class EntityRevision
  include PageObject

  a(:undo, css: '.mw-history-undo a')
  a(:restore, text: 'restore') #This link doesn't have any css identifier
end

class EntityHistoryPage
  include PageObject

  page_sections(:revisions, EntityRevision, css: 'ul#pagehistory > li')
end