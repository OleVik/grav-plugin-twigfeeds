# [Grav](http://getgrav.org/) Twig Feeds Plugin

Exposes RSS and Atom feeds to Grav, making them available for use in Twig. This means you can define RSS feeds in the plugin-configuration, then access them for iteration in templates.

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
| `cache_time` | 900 | integer | Default time, in seconds, to wait before caching data again. |
| `pass_headers` | `false` | `true` or `false` | Enables or disables passing ETag and Last Modified headers. |
| `twig_feeds` | List | List: `source`, `name`, `start`, `end`, `cache_time`, `extra_tags` | `source`: URL for a RSS or Atom feed; `name`: Custom title of feed; `start`: Item to start the results from; `end`: Item to end the results with; `cache_time`: Time, in seconds, to wait before caching data again.; `extra_tags`: List of additional tags to include from the feed. |

In addition to `enabled`, there is also a `cache`-option which enables the caching-mechanism. The `static_cache`-option changes the cache-location to /user/data, which makes feed-data persist beyond Grav's cache, and requires `cache: true`. This means that `bin/grav clearcache -all` does not invalidate the data, but it is still updated if Grav's cache is disabled and the plugin runs. The `debug`-option logs the execution of the plugin to Grav's Debugger and in /logs/grav.log.

The `cache_time`-option sets a default time to use when checking whether a feed should be cached again. This value should be no less than 300, as ETags and Last Modified headers are fickle and set by the target servers, and bypassing the plugins `cache_time` with values below 300 could lead to Exceptions being thrown by the PicoFeed-library. The `pass_headers`-option enables or disables passing ETag and Last Modified headers to the PicoFeed-library, thus relying solely on `cache_time` for preventing re-caching of data, which is more robust.

The `twig_feeds`-setting takes lists containing 5 properties: `source`, `name` `start`, and `end`, `cache_time`. Only the first one is required, which should point to a URL for a RSS or Atom feed. If `name` is set it is used as the key for the returned array, so you can iterate over this array only (see example below). `start` and `end` limits the returned results, where `start` is the item to start the results from, and `end` is the item to end the results with. `cache_time` is the amount of time, in seconds, to wait before caching results again.

For example, starting at 0 and ending at 10 would return a total of 10 items from the feed. You could also limit the results in Twig using the [slice-filter](http://twig.sensiolabs.org/doc/2.x/filters/slice.html) with `|slice(start, length)` or `[start:length]`.

**Note:** If you use a feed that is secured with HTTPS, then your server setup must be able to connect with this through Curl. Otherwise you'll get an error like this `curl: (60) SSL certificate problem: unable to get local issuer certificate`. A quick [how-to](https://www.saotn.org/dont-turn-off-curlopt_ssl_verifypeer-fix-php-configuration/). Further, your feed's encoding must match the encoding your server returns, or the PicoFeed-library's parser may fail.

Since v3.2.0 you can pass `extra_tags` to include non-standard tags, for example:

```
twig_feeds:
  - source: https://wtfpod.libsyn.com/rss
    end: 2
    extra_tags:
      - "itunes:duration"
      - "itunes:image":
        - href
```

This requires special handling in Twig: The returned tag can be a single array-item or contain multiple items, and if the tag contains a colon (`:`) you must treat this using Twig's `attribute()`. For example:

`{{ attribute(item, 'itunes:subtitle')|first }}`

This prints the first item of the `itunes:subtitle` tag, when placed within a regular loop of the feed's items. Further, since v3.2.2, you can pass any tag in `extra_tags` as a list with nested attributes. In the example above, the `href`-attribute of `itunes:image` will be retrieved, and the example returns:

    "itunes:duration" => array:1 [
      0 => "01:38:53"
    ]
    "itunes:image" => array:1 [
      "href" => array:1 [
        0 => "https://ssl-static.libsyn.com/p/assets/9/8/3/2/983246828eea14c6/WTF_-_new_larger_cover.jpg"
      ]
    ]

#### Caching

The `cache`-option relies on [Entity tags](https://en.wikipedia.org/wiki/HTTP_ETag) (ETags) and [Last Modified](https://fishbowl.pastiche.org/2002/10/21/http_conditional_get_for_rss_hackers/) headers. If the feed does not return these, then the cache is invalidated upon checking for new content. When set, the plugin checks whether the feed has modified content, and then stores the content locally in Grav's cache for subsequent use. This is superseded by `cache_time`, thus ETag and Last Modified headers are only checked if the time since the feed was last checked plus `cache_time` exceeds the current time.

#### Returned values
			
The plugin makes 8 properties available to each of the feeds in the `twig_feeds`-array. These are:

- `title`: The retrieved title of the feed.
- `name`: The declared name of the feed.
- `amount`: The total amount of returned results.
- `items`: All items in the feed, for iteration.
- `etag`: The ETag header of the feed.
- `last_modified`: The Last Modified header of the feed.
- `last_checked`: The Unix Timestamp when the feed was last checked by the plugin.
- `timestamp`: The Unix Timestamp when the feed was last modified, as a fallback to `last_modified`.

If the `name`-property is set, this is used for this name. If not, it defaults to the retrieved title.

#### Command Line Interface

As of version 3 the plugin supports CLI-usage, which means that you can clear or build the cache from settings independently of running it on a site. Two methods are available: `bin/plugin twigfeeds clearcache` and `bin/plugin twigfeeds buildcache`. The `clearcache`-command deletes cached files from the active cache location, using the plugin's settings to determine whether the cache is in `/cache` or `/user/data`.

The `buildcache`-command builds the cache, by default to the active cache location, which allows you to precache the feeds-data. It also uses the plugin's settings to determine the cache-location. You can also pass `--cache` to build the feeds to `/cache` or `--data` to build to `/user/data`.

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

We can also access any feed by its defined name:

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
