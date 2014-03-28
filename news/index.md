---
layout: default
title: News
---

{% for post in site.posts %}
  {% include news_item.html %}
{% endfor %}
