# [Grav](http://getgrav.org/) Twig Feeds Plugin

Exposes RSS and Atom feeds to Grav, making them available for use in Twig. This means you can define RSS feeds in the plugin-configuration, then access them for iteration in templates.

## Installation and Configuration

1. Download the zip version of [this repository](https://github.com/OleVik/grav-plugin-twigfeeds) and unzip it under `/your/site/grav/user/plugins`.
2. Rename the folder to `twigfeeds`.

You should now have all the plugin files under

    /your/site/grav/user/plugins/twigfeeds

The plugin is enabled by default, and can be disabled by copying `user/plugins/twigfeeds/twigfeeds.yaml` into `user/config/plugins/twigfeeds.yaml` and setting `enabled: false`.

## Settings and Usage

Variable names followed by options, with the default highlighted.

- `enabled`: **`true`**|`false` -- enables or disables plugin entirely
- `cache`: **`true`**|`false` -- enables or disables cache-mechanism
- `static_cache`: `true`|**`false`** -- makes cache-data persist beyond Grav's cache in `user/data` rather than `/cache`
- `debug`: `true`|**`false`** -- enables or disables debug-mode
- `log_file`: `String`|**`"twigfeeds.log"`** -- where to log parser-output, relative to `log://`, or `false` to disable
- `cache_time`: `Integer`|**`900`** -- default time, in seconds, to wait before caching data again
- `pass_headers`: `true`|**`false`** -- enables or disables passing ETag and Last Modified headers

### Options per feed

- `twig_feeds`: `List` -- settings for each feed to query and parse
  - `source`: `String` -- URL for a RSS or Atom feed
  - `name`: `String` -- custom title for the feed
  - `start`: `Integer` -- item to start the results from
  - `end`: `Integer` -- item to end the results with
  - `mode`: `String`|**`default`** -- 'default', 'direct' or 'raw', see details below
  - `cache_time`: `Integer` -- same as default setting, but for this feed
  - `request_options`: `List` -- same as default setting, but for this feed
  - `categories`: `List` -- List of strings to categorize this feed
  - `tags`: `List` -- List of strings to tag this feed

### Request Options

These are considered an option for expert-users, and passed directly to Guzzle Client, see [their docs](http://docs.guzzlephp.org/en/stable/request-options.html). Because of the variety in and amount of options, it is not made available per feed in the plugin's blueprints.

- `request_options`: `List` -- defaults to use with the query-library
  - `allow_redirects`: **`true`**|`false`
  - `connect_timeout`: `Integer`|**`30`**
  - `timeout`: `Integer`|**`30`**
  - `http_errors`: **`false`**|`true`

### Settings in detail

The `cache`-option enables the caching-mechanism. The `static_cache`-option changes the cache-location to `/user/data`, which makes feed-data persist beyond Grav's cache, and requires `cache: true`. This means that `bin/grav clearcache -all` does not invalidate the data, but it is still updated if Grav's cache is disabled and the plugin runs. The `debug`-option logs the execution of the plugin to Grav's Debugger and in `/logs/grav.log`.

The `cache_time`-option sets a default time to use when checking whether a feed should be cached again. This value should be no less than 300, as ETags and Last Modified headers are fickle and set by the target servers, and bypassing the plugins `cache_time` with values below 300 could lead to exceptions being thrown by the underlying parser-library. The `pass_headers`-option enables or disables passing ETag and Last Modified headers to the parser-library, thus relying solely on `cache_time` for preventing re-caching of data, which is more robust.

The `twig_feeds`-setting takes lists containing 5 properties: `source`, `name` `start`, `end`, `cache_time`, and `request_options`. Only the first one is required, which should point to a URL for a RSS or Atom feed. If `name` is set it is used as the key for the returned array, so you can iterate over this array only. `start` and `end` limits the stored results, where `start` is the item to start the results from, and `end` is the item to end the results with. `cache_time` is the amount of time, in seconds, to wait before caching results again.

For example, starting at 0 and ending at 10 would return a total of 10 items from the feed. You could also limit the results in Twig using the [slice-filter](http://twig.sensiolabs.org/doc/2.x/filters/slice.html) with `|slice(start, length)` or `[start:length]`.

**Note:** If your feed's `source` is secured with HTTPS, then your server setup must be able to connect with this through Curl. Otherwise you'll get an error like this `curl: (60) SSL certificate problem: unable to get local issuer certificate`. A quick [how-to](https://www.saotn.org/dont-turn-off-curlopt_ssl_verifypeer-fix-php-configuration/). Further, your feed's encoding must match the encoding your server returns, or the parser-library may fail.

**Modes:** For most use-cases, the _'default'_-mode is the best way to get a feed as it will be parsed normally including header-checks. Some servers, however, do not implement responses or feeds correctly, in which case the _'direct'_-mode allows you to bypass the header-checks but still do some very basic parsing to normalize the feed-data. The _'raw'_-mode performs no checks or normalizing.

All tags, including non-standard ones, are retrieved. Eg., `itunes:duration` can be found in the `elements`-array of a feed item that has it, and could return `01:21:43`. Depending on how the feed sets the data in non-standard tags, it may require special handling in Twig: The returned tag can be a single array-item or contain multiple items, and if the tag contains a colon (`:`) you must treat this using Twig's `attribute()`. For example:

`{{ attribute(item, 'itunes:subtitle')|first }}`

## Returned values

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

## Examples

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
<small
  >Retrieved title: <a href="{{ feed.config.source }}">{{ feed.title }}</a>, {{
  feed.items|length }} item(s)</small
>
{% for item in feed.items %}
<h5>
  <a href="{{ item.link }}">{{ item.title|default(item.link) }}</a>
</h5>
<time>{{ item.lastModified }}</time>
<p>{{ item.description }}</p>
{% endfor %} {% endfor %}
```

This will iterate over each feed and output the name, retrieved title as a link, amount of items, and the first two items in each feed.

We can also access any feed by its defined name:

```html
{% for name, feed in twig_feeds if name == 'NY Times' %}
<h4>Feed name: {{ name }}</h4>
<small
  >Retrieved title: <a href="{{ feed.source }}">{{ feed.title }}</a>, {{
  feed.amount }} item(s)</small
>
{% for item in feed.items %}
<h5>
  <a href="{{ item.link }}">{{ item.title|default(item.link) }}</a>
</h5>
<time>{{ item.lastModified }}</time>
<p>{{ item.description }}</p>
{% endfor %} {% endfor %}
```

### Aggregate

Or if you want to aggregate a bunch of feeds, you could do:

```twig
{% set feed_items = [] %}
{% for name, feed in twig_feeds %}
  {% set feed_items = feed_items|merge(feed.items) %}
{% endfor %}
```

### Paginate

Further, you could paginate many items like this:

```twig
{% set index = 1 %}
{% set feed_items = [] %}
{% for name, feed in twig_feeds %}
  {% for item in feed.items %} {% set index = index + 1 %}
    {% set item = item|merge({ 'retrievedTitle': feed.title }) %}
    {% set item = item|merge({'sortDate': item.lastModified }) %}
    {% set feed_items = feed_items|merge({(index): (item) }) %}
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
  {% for i in 1..totalPages %} {% if (currentPage - paginationLimit) -
  loop.index == 0 %}
  <li class="page-item">
    <span>&hellip;</span>
  </li>
  {% elseif (currentPage + paginationLimit) - loop.index == 0 %}
  <li class="page-item">
    <span>&hellip;</span>
  </li>
  {% elseif (currentPage - paginationLimit) - loop.index > 0 %} {% elseif
  (currentPage + paginationLimit) - loop.index < 0 %} {% else %}
  <li class="page-item {% if currentPage == loop.index  %} active{% endif %}">
    <a href="{{ page.url(true) }}/page:{{ loop.index }}">{{ loop.index }}</a>
  </li>
  {% endif %} {% endfor %}
  <li class="page-item {% if currentPage >= totalPages %}disabled{% endif %}">
    <a href="{{ page.url(true) }}/page:{{ currentPage + 1 }}">Next</a>
  </li>
  <li class="page-item {% if currentPage >= totalPages %}disabled{% endif %}">
    <a href="{{ page.url(true) }}/page:{{ totalPages }}">Last</a>
  </li>
</ul>
{% endif %}
```

This last example is based on [this Gist](https://gist.github.com/maxpou/612359ed4af4cc5c4f06). Pages are indexed in the format `http://domain.tld/page:1`, where the `page`-parameter increases for each consecutive page.

### Taxonomy: Adding and utilizing additional metadata

Since version 5.1.0, you can add taxonomy like category- or tag-properties to your feeds, to sort, filter, and otherwise manipulate the feeds themselves. Based on [a post on the Discourse-forum](https://discourse.getgrav.org/t/twigfeeds-rss-feed-labelling-categorisation/) by [Penworks](https://github.com/Penworks).

In iteration, `{{ feed.config.categories }}` and `{{ feed.config.tags }}` are available.

## Caching

The `cache`-option relies on [Entity tags](https://en.wikipedia.org/wiki/HTTP_ETag) (ETags) and [Last Modified](https://fishbowl.pastiche.org/2002/10/21/http_conditional_get_for_rss_hackers/) headers. If the feed does not return these, then the cache is invalidated upon checking for new content. When set, the plugin checks whether the feed has modified content, and then stores the content locally in the cache for subsequent use. This is superseded by `cache_time`, thus ETag and Last Modified headers are only checked if the time since the feed was last checked plus `cache_time` exceeds the current time.

## Command Line Interface

The plugin supports CLI-usage, which means that you can build or clear the cache from settings independently of running it on a site. Two methods are available: `bin/plugin twigfeeds build` and `bin/plugin twigfeeds clear`. The `build`-command creates the cached files, whilst the `clear`-command deletes cached files from the active or selected cache-location. This allows a cronjob to run the CLI to cache routinely, such that the visitor is not as exposed to re-caches. It will respect your plugin-configuration, and so running it frequently will not unecessarily process anything that would not run otherwise by a visit to the site.

## Testing

In `/tests/feeds.json` there's an array of objects in the form `[{"source": "URL", "mode": "MODE"}]`, which can be ran with PHPUnit to verify the validity of sources and structure of returned data. See environment-variables in `/phpunit.xml` for settings.

## Major-version changes

When upgrading to a new major-version, be aware that your old settings may not work as expected anymore. Always test locally before using in production. By definition, a major-version includes backwards-incompatible changes.

### Changes in v5

The plugin now requires PHP version 8 or higher, following the parser-library's update, and Grav version 1.7 or higher. The blueprints are now destructured into separate files which [theoretically](https://github.com/getgrav/grav-plugin-admin/issues/2458) can be overridden by the local user, allowing for additions such as metadata per feed.

More details on the specification the new library, FeedIo, uses [is available here](https://github.com/php-feed-io/feed-io/blob/main/docs/specifications-support.md). Changes to options:

- The `extra_tags` option has been deprecated, all tags are now included by default
- A `request_options` option has been added, which allows you to [pass options to the Guzzle Client](http://docs.guzzlephp.org/en/stable/request-options.html)
- The plugin now uses PSR-4 for autoloading

### Changes in v4

PicoFeed was deprecated long ago, and hasn't been properly maintained in most forks. This caused some errors to be persistent, that could not be resolved without forking and patching the library. Thus, though the API in PHP remains largely intact, it has changed in Twig.

Some properties will have different names than before. To get an idea of what the data looks like, use `{{ dump(twig_feeds) }}` in a Twig-template and inspect the debugger. Most notably, `item.url` is now `item.link`, `item.content` is `item.description`, and `item.date.date` is `item.lastModified`.

## License

MIT License 2019-2025 by [Ole Vik](http://github.com/olevik).
