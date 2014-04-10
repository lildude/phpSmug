#!/usr/bin/env ruby

require 'nokogiri'
require 'open-uri'
require 'upmark'

# Grab the contents of our feed
#doc = Nokogiri::XML(open('http://phpsmug.com/atom/1'))
doc = Nokogiri::XML(open('export.xml'))

# Remove namespaces as I'm lazy and we really don't need them right now
doc.remove_namespaces!

# Grab all the entries
items = doc.xpath('//entry')

# Now iterate through each entry
items.each do |item|
  title = item.at_xpath('title').text.strip
  print "Parsing #{title}... "
  content = Upmark.convert(item.at_xpath('content').text)
  name = item.at_xpath('link')['href'].split('/')[-1]
  date_str = item.at_xpath('published').text
  date = Time.parse(date_str)
  filename = date.strftime("%Y-%m-%d-"+name+".md")
  path = filename
  if not File.exists?(path)
    File.open(path, 'w') do |f|
      f.puts <<DOC
---
layout: news_item
title: "#{title}"
date: "#{date}"
author: lildude
categories:
---

DOC
      f.puts content
    end
    puts "Done"
    else
    puts "Exists"
  end
end
