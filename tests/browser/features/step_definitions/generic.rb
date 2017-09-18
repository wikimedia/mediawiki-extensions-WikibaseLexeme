AfterStep('@pause') do
  print "Press Return to continue ..."
  STDIN.getc
end

AfterStep('@slow') do
  sleep 2
end

Then(/^VisualEditor welcome message is disabled$/) do
  cookie = "$.cookie( 've-beta-welcome-dialog', 1, { path: '/', expires: 30 } );"
  @browser.execute_script(cookie)
end