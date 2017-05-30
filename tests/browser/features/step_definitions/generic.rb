AfterStep('@pause') do
  print "Press Return to continue ..."
  STDIN.getc
end

AfterStep('@slow') do
  sleep 2
end