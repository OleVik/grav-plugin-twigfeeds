# [Grav](http://getgrav.org/) Twig Feeds Plugin

Exposes RSS and Atom feeds to Grav, making them available for use in Twig. This means you can define RSS feeds in the plugin-configuration, then access them for iteration in templates.

## Installation and Configuration

1. Download the zip version of [this repository](https://github.com/OleVik/grav-plugin-twigfeeds) and unzip it under `/your/site/grav/user/plugins`.
2. Rename the folder to `twigfeeds`.

You should now have all the plugin files under

    /your/site/grav/user/plugins/twigfeeds

The plugin is enabled by default, and can be disabled by copying `user/plugins/twigfeeds/twigfeeds.yaml` into `user/config/plugins/twigfeeds.yaml` and setting `enabled: false`.

## Changes in v4.0.0

PicoFeed was deprecated long ago, and hasn't been properly maintained in most forks. This caused some errors to be persistent, that could not be resolved without forking and patching the library, which the creator of this plugin was unwilling to do. Thus, though the API in PHP remains largely intact, it has changed in Twig.

**You will need to revise your templates to make sure everything works as expected, some properties will have different names than before.** To get an idea of what the data looks like, use `{{ dump(twig_feeds) }}` in a Twig-template and inspect the debugger. Most notably, `item.url` is now `item.link`, `item.content` is `item.description`, and `item.date.date` is `item.lastModified`. The change is not backwards-compatible, so update your templates as needed.

More details on the specification the new library, FeedIo, uses [is available here](https://github.com/alexdebril/feed-io/blob/master/doc/specifications-support.md). Changes to options:

- The `extra_tags` option has been deprecated, all tags are now included by default
- A `request_options` option has been added, which allows you to [pass options to the Guzzle Client](http://docs.guzzlephp.org/en/stable/request-options.html)
- The plugin now uses PSR-4 for autoloading

### Settings and Usage

| Variable | Default | Options | Note |
|----------------|---------|----------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------|
| `enabled` | `true` | `true` or `false` | Enables or disables plugin entirely. |
| `cache` | `true` | `true` or `false` | Enables or disables cache-mechanism. |
| `static_cache` | `false` | `true` or `false` | Makes cache-data persist beyond Grav's cache. |
| `debug` | `false` | `true` or `false` | Enables or disables debug-mode. |
| `cache_time` | 900 | integer | Default time, in seconds, to wait before caching data again. |
| `pass_headers` | `false` | `true` or `false` | Enables or disables passing ETag and Last Modified headers. |
| `request_options` | List:  `allow_redirects: true`  `connect_timeout: 30`  `timeout: 30`  `http_errors: false` | List:  `allow_redirects`  `connect_timeout`  `timeout`  `http_errors` | Options to use with the Guzzle Client, see [their docs](http://docs.guzzlephp.org/en/stable/request-options.html). |
| `twig_feeds` | List: ... | List:  `source`  `name`  `start`  `end`  `cache_time` | `source`: URL for a RSS or Atom feed; `name`: Custom title of feed; `start`: Item to start the results from; `end`: Item to end the results with; `cache_time`: Time, in seconds, to wait before caching data again. |

In addition to `enabled`, there is also a `cache`-option which enables the caching-mechanism. The `static_cache`-option changes the cache-location to /user/data, which makes feed-data persist beyond Grav's cache, and requires `cache: true`. This means that `bin/grav clearcache -all` does not invalidate the data, but it is still updated if Grav's cache is disabled and the plugin runs. The `debug`-option logs the execution of the plugin to Grav's Debugger and in /logs/grav.log.

The `cache_time`-option sets a default time to use when checking whether a feed should be cached again. This value should be no less than 300, as ETags and Last Modified headers are fickle and set by the target servers, and bypassing the plugins `cache_time` with values below 300 could lead to Exceptions being thrown by the PicoFeed-library. The `pass_headers`-option enables or disables passing ETag and Last Modified headers to the PicoFeed-library, thus relying solely on `cache_time` for preventing re-caching of data, which is more robust.

The `twig_feeds`-setting takes lists containing 5 properties: `source`, `name` `start`, and `end`, `cache_time`. Only the first one is required, which should point to a URL for a RSS or Atom feed. If `name` is set it is used as the key for the returned array, so you can iterate over this array only (see example below). `start` and `end` limits the returned results, where `start` is the item to start the results from, and `end` is the item to end the results with. `cache_time` is the amount of time, in seconds, to wait before caching results again.

For example, starting at 0 and ending at 10 would return a total of 10 items from the feed. You could also limit the results in Twig using the [slice-filter](http://twig.sensiolabs.org/doc/2.x/filters/slice.html) with `|slice(start, length)` or `[start:length]`.

**Note:** If you use a feed that is secured with HTTPS, then your server setup must be able to connect with this through Curl. Otherwise you'll get an error like this `curl: (60) SSL certificate problem: unable to get local issuer certificate`. A quick [how-to](https://www.saotn.org/dont-turn-off-curlopt_ssl_verifypeer-fix-php-configuration/). Further, your feed's encoding must match the encoding your server returns, or the PicoFeed-library's parser may fail.

Since v4.0.0 all tags, including non-standard ones, are retrieved. Eg., `itunes:duration` can be found in the `elements`-array of a feed item that has it, and could return `01:21:43`. Depending on how the feed sets the data in non-standard tags, it may require special handling in Twig: The returned tag can be a single array-item or contain multiple items, and if the tag contains a colon (`:`) you must treat this using Twig's `attribute()`. For example:

`{{ attribute(item, 'itunes:subtitle')|first }}`

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

The plugin supports CLI-usage, which means that you can clear or build the cache from settings independently of running it on a site. Two methods are available: `bin/plugin twigfeeds clearcache` and `bin/plugin twigfeeds buildcache`. The `clearcache`-command deletes cached files from the active cache location, using the plugin's settings to determine whether the cache is in `/cache` or `/user/data`.

The `buildcache`-command builds the cache, by default to the active cache location, which allows you to precache the feeds-data. It also uses the plugin's settings to determine the cache-location. You can also pass `--cache` to build the feeds to `/cache` or `--data` to build to `/user/data`.

#### Examples

Consider the following settings in `user/config/plugins/twigfeeds.yaml`:

```yaml
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

```html
{% for name, feed in twig_feeds %}
    <h4>Feed name: {{ name }}</h4>
    <small>Retrieved title: <a href="{{ feed.source }}">{{ feed.title }}</a>, {{ feed.amount }} item(s)</small>
    {% for item in feed.items %}
        <h5>
            <a href="{{ item.link }}">{{ item.title }}</a>
        </h5>
        <time>{{ item.lastModified }}</time>
        <p>{{ item.description }}</p>
    {% endfor %}
{% endfor %}
```

This will iterate over each feed and output the name, retrieved title (as a link), amount of items, and the first two items in each feed.

We can also access any feed by its defined name:

```html
{% for name, feed in twig_feeds if name == 'NY Times' %}
    <h4>Feed name: {{ name }}</h4>
    <small>Retrieved title: <a href="{{ feed.source }}">{{ feed.title }}</a>, {{ feed.amount }} item(s)</small>
    {% for item in feed.items %}
        <h5>
            <a href="{{ item.link }}">{{ item.title }}</a>
        </h5>
        <time>{{ item.lastModified }}</time>
        <p>{{ item.description }}</p>
    {% endfor %}
{% endfor %}
```

Or if you want to aggregate a bunch of feeds, you could do:

```html
{% set feed_items = [] %}
{% for name, feed in twig_feeds %}
    {% set feed_items = feed_items|merge(feed.items) %}
{% endfor %}
```

Further, you could paginate many items like this:

```html
{% set index = 1 %}
{% set feed_items = [] %}
{% for name, feed in twig_feeds %}
    {% for item in feed.items %}
        {% set index = index + 1 %}
        {% set item = item|merge({ 'retrievedTitle': feed.title }) %}
        {% set item = item|merge({ 'sortDate': item.lastModified }) %}
        {% set feed_items = feed_items|merge({ (index): (item) }) %}
    {% endfor %}
{% endfor %}
{% if uri.param('page') %}
    {% set currentPage = uri.param('page') %}
{% else %}
    {% set currentPage = 1 %}
{% endif %}
{% set perPage = 5 %}
{% set totalPages = (feed_items|length / perPage)|round(0, 'ceil') %}
{% set start = currentPage * perPage - perPage %}
{% set paginationLimit = 4 %}

{% for index, item in feed_items|sort_by_key('sortDate')|reverse|slice(start, perPage) %}
    <h5>
        <a href="{{ item.link }}">{{ item.retrievedTitle }} - {{ item.title }}</a>
    </h5>
    <time>{{ item.lastModified }}</time>
{% endfor %}

{% if totalPages > 1 %}
    <ul class="pagination">
        <li class="page-item {% if currentPage <= 1 %}disabled{% endif %}">
            <a href="{{ page.url(true) }}/page:{{ 1 }}">First</a>
        </li>
        <li class="page-item {% if currentPage <= 1 %}disabled{% endif %}">
            <a href="{{ page.url(true) }}/page:{{ currentPage - 1 }}">Previous</a>
        </li>
        {% for i in 1..totalPages %}
            {% if (currentPage - paginationLimit) - loop.index == 0 %}
                <li class="page-item">
                    <span>&hellip;</span>
                </li>
            {% elseif (currentPage + paginationLimit) - loop.index == 0 %}
                <li class="page-item">
                    <span>&hellip;</span>
                </li>
            {% elseif (currentPage - paginationLimit) - loop.index > 0 %}
            {% elseif (currentPage + paginationLimit) - loop.index < 0 %}
            {% else %}
                <li class="page-item {% if currentPage == loop.index  %} active{% endif %}">
                    <a href="{{ page.url(true) }}/page:{{ loop.index }}">{{ loop.index }}</a>
                </li>
            {% endif %}
        {% endfor %}
        <li class="page-item {% if currentPage >= totalPages %}disabled{% endif %}">
            <a href="{{ page.url(true) }}/page:{{ currentPage + 1 }}">Next</a>
        </li>
        <li class="page-item {% if currentPage >= totalPages %}disabled{% endif %}">
            <a href="{{ page.url(true) }}/page:{{ totalPages }}">Last</a>
        </li>
    </ul>
{% endif %}
```

This last example is based on [this Gist](https://gist.github.com/maxpou/612359ed4af4cc5c4f06), tested with Grav v1.4.0-rc.1, plugin v3.3.0. Pages are indexed in the format `http://domain.tld/page:1`, where the `page`-parameter increases for each consecutive page.

MIT License 2019 by [Ole Vik](http://github.com/olevik).

## License
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2FOleVik%2Fgrav-plugin-twigfeeds.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2FOleVik%2Fgrav-plugin-twigfeeds?ref=badge_large)
