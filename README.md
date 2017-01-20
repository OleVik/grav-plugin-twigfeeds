# [Grav](http://getgrav.org/) Twig Feeds Plugin

Exposes RSS and Atom feeds to Grav, making them available for use in Twig. This means you can define a RSS feed in the plugin-configuration, then access them for iteration in Twig-templates.

## Installation and Configuration

1. Download the zip version of [this repository](https://github.com/OleVik/grav-plugin-twigfeeds) and unzip it under `/your/site/grav/user/plugins`.
2. Rename the folder to `twigfeeds`.

You should now have all the plugin files under

    /your/site/grav/user/plugins/twigfeeds

The plugin is enabled by default, and can be disabled by copying `user/plugins/twigfeeds/twigfeeds.yaml` into `user/config/plugins/twigfeeds.yaml` and setting `enabled: false`.

### Settings and Usage

The `twig_feeds`-setting takes lists containing 4 properties: `source`, `name` `start`, and `end`. Only the first one is required, which should point to a URL for a RSS or Atom feed. If `name` is set it is used as the key for the returned array, so you can iterate over this array only (see example below). The latter two limits the returned results, where `start` is the item to start the results from, and `end` is the item to end the results with.

For example, starting at 0 and ending at 10 would return a total of 10 items from the feed. You could also limit the results in Twig using the [slice-filter](http://twig.sensiolabs.org/doc/2.x/filters/slice.html) with `|slice(start, length)` or `[start:length]`.

**Note:** If you use a feed that is secured with HTTPS, then your server setup must be able to connect with this through Curl. Otherwise you'll get an error like this `curl: (60) SSL certificate problem: unable to get local issuer certificate`. A quick [how-to](https://www.saotn.org/dont-turn-off-curlopt_ssl_verifypeer-fix-php-configuration/).

#### Returned values
			
The plugin makes 6 properties available to each of the feeds in the `twig_feeds`-array. These are:

- `title`: The retrieved title from the feed.
- `source`: The initially defined URL for the feed.
- `start`: The start of the returned results, if set.
- `end`: The end of the returned results, if set.
- `amount`: The total amount of returned results, computed.
- `items`: All items in the feed, for iteration.

Additionally, each feed has a corresponding name - as the returned array is an associative array - which can be accessed by Twig (see examples below). If the `name`-property is set, this is used for this name. If not, it defaults to the retrieved title.

#### Examples

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
{% for name, feed in twig_feeds %}
	<h4>Feed name: {{ name }}</h4>
	<small>Retrieved title: <a href="{{ feed.source }}">{{ feed.title }}</a>, {{ feed.amount }} item(s)</small>
	{% for item in feed.items %}
		<h5>
			<a href="{{ item.url }}">{{ item.title }}</a>
		</h5>
		<time>{{ item.date.date }}</time>
		<p>{{ item.content }}</p>
	{% endfor %}
{% endfor %}
```

This will iterate over each feed and output the name, retrieved title (as a link), amount of items, and the first two items in each feed.

Since v1.1.0 we can also access any defined feed by name, like this:

```
{% for name, feed in twig_feeds if name == 'NY Times' %}
	<h4>Feed name: {{ name }}</h4>
	<small>Retrieved title: <a href="{{ feed.source }}">{{ feed.title }}</a>, {{ feed.amount }} item(s)</small>
	{% for item in feed.items %}
		<h5>
			<a href="{{ item.url }}">{{ item.title }}</a>
		</h5>
		<time>{{ item.date.date }}</time>
		<p>{{ item.content }}</p>
	{% endfor %}
{% endfor %}
```

MIT License 2017 by [Ole Vik](http://github.com/olevik).
