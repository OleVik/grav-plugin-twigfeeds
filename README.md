# [Grav](http://getgrav.org/) Twig Feeds Plugin

Exposes RSS and Atom feeds to Grav, making them available for use in Twig. This means you can define a RSS feed in the plugin-configuration, then access them for iteration in Twig-templates.

## Installation and Configuration

1. Download the zip version of [this repository](https://github.com/OleVik/grav-plugin-twigfeeds) and unzip it under `/your/site/grav/user/plugins`.
2. Rename the folder to `twigfeeds`.

You should now have all the plugin files under

    /your/site/grav/user/plugins/twigfeeds

The plugin is enabled by default, and can be disabled by copying `user/plugins/twigfeeds/twigfeeds.yaml` into `user/config/plugins/twigfeeds.yaml` and setting `enabled: false`.

### Settings and Usage

The `twig_feeds`-setting takes lists containing three properties: `source`, `start`, and `end`. Only the first one is required, which should point to a URL for a RSS or Atom feed. The latter two limits the returned results, where `start` is the item to start the results from, and `end` is the item to end the results with.

For example, starting at 0 and ending at 10 would return a total of 10 items from the feed. You could also limit the results in Twig using the [slice-filter](http://twig.sensiolabs.org/doc/2.x/filters/slice.html) with `|slice(start, length)` or `[start:length]`.

Consider the following settings in `user/config/plugins/twigfeeds.yaml`:

```
enabled: true
twig_feeds:
  - source: http://rss.nytimes.com/services/xml/rss/nyt/World.xml
    start: 0
    end: 2
  - source: http://feeds.bbci.co.uk/news/uk/rss.xml
    start: 0
    end: 2
```

This retrieves World News from The New York Times and UK News from the BBC, which we can use in any Twig-template like this:

```
{% for title, feed in twig_feeds %}
	<h4>{{ title }}</h4>
	{% for item in feed %}
		<h5>
			<a href="{{ item.url }}">{{ item.title }}</a>
		</h5>
		<time>{{ item.date.date }}</time>
		<p>{{ item.content }}</p>
	{% endfor %}
{% endfor %}
```

This will iterate over each feed, and output the title and the first two items in each feed.

MIT License 2017 by [Ole Vik](http://github.com/olevik).
