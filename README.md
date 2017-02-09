# [Grav](http://getgrav.org/) Twig Feeds Plugin

Exposes RSS and Atom feeds to Grav, making them available for use in Twig. This means you can define a RSS feed in the plugin-configuration, then access them for iteration in Twig-templates.

## Installation and Configuration

1. Download the zip version of [this repository](https://github.com/OleVik/grav-plugin-twigfeeds) and unzip it under `/your/site/grav/user/plugins`.
2. Rename the folder to `twigfeeds`.

You should now have all the plugin files under

    /your/site/grav/user/plugins/twigfeeds

The plugin is enabled by default, and can be disabled by copying `user/plugins/twigfeeds/twigfeeds.yaml` into `user/config/plugins/twigfeeds.yaml` and setting `enabled: false`.

### Settings and Usage

| Variable | Default | Options | Note |
|----------------|---------|----------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------|
| `enabled` | `true` | `true` or `false` | Enables or disables plugin entirely. |
| `cache` | `true` | `true` or `false` | Enables or disables cache-mechanism. |
| `static_cache` | `false` | `true` or `false` | Makes cache-data persist beyond Grav's cache. |
| `debug` | `false` | `true` or `false` | Enables or disables debug-mode. |
| `twig_feeds` | List | List: `source`, `name`, `start`, `end` | `source`: URL for a RSS or Atom feed; `name`: Custom title of feed; `start`: Item to start the results from; `end`: Item to end the results with. |

In addition to `enabled`, there is also a `cache`-option which enables the caching-mechanism. The `static_cache`-option changes the cache-location to /user/data, which makes feed-data persistant beyond Grav's cache. This means that `bin/grav clear-cache` does not invalidate the data, but it is still updated if Grav's cache is disabled and the plugin runs.

The `debug`-option logs simple details to Grav's Debugger and in /logs/grav.log, which aids in identifying what `state` the feed is in (cached or uncached), what `type` of data the feed is parsed as, the `action` (eg. "add_>feed_items: FILENAME"), and the content of the feed-data. The log-file does not include the content.

The `twig_feeds`-setting takes lists containing 4 properties: `source`, `name` `start`, and `end`. Only the first one is required, which should point to a URL for a RSS or Atom feed. If `name` is set it is used as the key for the returned array, so you can iterate over this array only (see example below). The latter two limits the returned results, where `start` is the item to start the results from, and `end` is the item to end the results with.

For example, starting at 0 and ending at 10 would return a total of 10 items from the feed. You could also limit the results in Twig using the [slice-filter](http://twig.sensiolabs.org/doc/2.x/filters/slice.html) with `|slice(start, length)` or `[start:length]`.

**Note:** If you use a feed that is secured with HTTPS, then your server setup must be able to connect with this through Curl. Otherwise you'll get an error like this `curl: (60) SSL certificate problem: unable to get local issuer certificate`. A quick [how-to](https://www.saotn.org/dont-turn-off-curlopt_ssl_verifypeer-fix-php-configuration/).

#### Caching

The `cache`-option relies on two settings in the feed: [Entity tags](https://en.wikipedia.org/wiki/HTTP_ETag) (ETags) and [last modified](https://fishbowl.pastiche.org/2002/10/21/http_conditional_get_for_rss_hackers/) header. If the feed does not return these, then the cache is invalidated upon checking for new content. When set, the plugin checks whether the feed has modified content, and then stores the content locally in Grav's cache for subsequent use.

The process is the following:

1. Plugin-cache is enabled, a `manifest.json` is built in /cache/twigfeeds containing feed sources with related `etag`, `last_modified`, `filename`, and `timestamp`.
2. On subsequent iterations, this file (if it exists) is used to retrieve data from associated `filename`'s.
3. If the feed is cached, the content from these files is used to return feed contents.

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
