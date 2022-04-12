# v5.0.0-beta.2
## 12-04-2022

1. [](#bugfix)
    * Debug-parameter (#33), thanks @hughbris
    * Testing-parameter (#32), thanks @hughbris

# v5.0.0-beta.1
## 02-02-2022

1. [](#new)
    * Major-version bump of FeedIO (#30, #31), thanks @lesion
2. [](#improved)
    * Require PHP v8
    * Require Grav v1.7
    * Remove PicoFeed-legacy [#ca09a6b](https://github.com/OleVik/grav-plugin-twigfeeds/pull/31/commits/ca09a6b0c65be52adbc6441b2c7b200e4acba4b9) and cleanup

# v4.0.1
## 16-10-2019

1. [](#bugfix)
    * Wrong variable in Parser

# v4.0.0
## 13-10-2019

1. [](#new)
    * Stable release of 4.0.0 refactor

# v4.0.0-beta.1
## 01-10-2019

1. [](#new)
    * Refactor: Replaced PicoFeed with FeedIO
    * The `extra_tags` option has been deprecated
    * A `request_options` option has been added
2. [](#improved)
    * Error-handling
    - Use PSR-4 for autoloading
    - Blueprints, Translations

# v3.3.4
## 16-01-2019

1. [](#bugfix)
    * Silent fallback from file exists check
    * Change filename-hash to use entire source-URL
    * Fix config-reference in Parser
2. [](#improved)
    * Grav version requirement bump

# v3.3.3
## 08-01-2019

1. [](#bugfix)
    * Fix config-reference in Parser

# v3.3.2
## 03-06-2018

1. [](#bugfix)
    * Change filename-hash to use entire source-URL

# v3.3.1
## 11-03-2018

1. [](#improved)
    * In the case of a timeout, fail silently and return empty

# v3.3.0
## 27-09-2017

1. [](#improved)
    * Option to silence security errors

# v3.3.0-beta.1.1
## 17-09-2017

1. [](#improved)
    * Option to silence security errors

# v3.2.4
## 22-06-2017

1. [](#bugfix)
    * Cache-check in Parser->parseFeed()

# v3.2.3
## 01-06-2017

1. [](#bugfix)
    * Removed Symfony/YAML from /vendor, its already in core
    * Replaced getGrav() with Grav::instance() in CLI-commands

# v3.2.2
## 29-05-2017

1. [](#new)
    * Facilitate attributes-retrieval in non-standard tags
2. [](#improved)
    * Add note and example to README regarding attributes

# v3.2.1
## 15-05-2017

1. [](#bugfix)
    * Dependency version-range compatibly with Grav >=1.1.17

# v3.2.0
## 14-05-2017

1. [](#improved)
    * Facilitate non-standard tags in feed parsing
    * Add note and example to README regarding non-standard tags
    * Hash filenames to prevent conflict
    * Minor cleanup
2. [](#bugfix)
    * Only recreate manifest after busting cache

# v3.1.2
## 14-05-2017

1. [](#bugfix)
    * Fix logic of updating manifest
    * Remove Al Jazeera from example feeds, their feed-encoding is currently broken and will throw errors

# v3.1.1
## 14-04-2017

1. [](#improved)
    * Version-bump out of pre-release

# v3.1.0
## 30-03-2017

1. [](#improved)
    * Add method for busting cache on version update (major or minor)
    * Add test for versions
    * Update vendor libraries
2. [](#new)
    * Add Manifest->blueprintVersion
    * Add Parser->getVersion() for retrieving blueprint or manifest version
    * Add Parser->compareSemVer() for comparing versions
    * Add Symfony/Component/Yaml and Naneau/SemVer libraries to Parser
3. [](#bugfix)
    * Fix Manifest->manifestStructure() time-declarations
    * Fix changelog date for v3.0.4


# v3.0.4
## 28-03-2017

1. [](#bugfix)
    * Fix Exception-call

# v3.0.3
## 19-03-2017

1. [](#bugfix)
    * Add Exception for empty path passed to Parser->parseFeed()

# v3.0.2
## 09-03-2017

1. [](#bugfix)
    * Include 'name' and 'amount' in Manifest->compare()

# v3.0.1
## 08-03-2017

1. [](#new)
    * Version 3 release
2. [](#improved)
    * Plugin icon
    * Update README

# v3.0.0
## 27-02-2017

1. [](#improved)
    * OOP-rewrite of entire plugin
    * Simplify process and clarify inline comments
    * Update README
    * Default limit of retrieved and stored feed items to 50
    * Default 'cache_time' set to 15 minutes (900 seconds)
    * Default 'pass_headers' set to false, relying on 'cache_time' by default
2. [](#new)
    * Add 'cache_time' setting
    * Add 'pass_headers' setting
    * Document classes and methods
    * Add Command Line Interface for clearing and building cache
3. [](#bugfix)
    * If plugin-config changes, cache is busted to ensure correct data

# v2.0.4
## 26-02-2017

1. [](#bugfix)
    * Add onTwigPageVariables to getSubscribedEvents

# v2.0.3
## 15-02-2017

1. [](#bugfix)
    * Cache custom name

# v2.0.2
## 09-02-2017

1. [](#bugfix)
    * Fix Changelog format

# v2.0.1
## 09-02-2017

1. [](#bugfix)
    * Blueprint list-field temporary fix
    * DateTime fallback
2. [](#improved)
    * Debug message clarity

# v2.0.0
## 28-01-2017

1. [](#improved)
    * Added cache-functionality
2. [](#new)
    * Added cache, static_cache, and debug options

# v1.2.0
## 28-01-2017

1. [](#improved)
    * More specific Exceptions by vendor-library and plugin
    * Declarative timezone: UTC
2. [](#bugfix)
     * Changelog date format

# v1.1.0
## 20-01-2017

1. [](#improved)
    * Added name-property to config and blueprint
    * Added return values to returned array
    * Changed return values to more declarative syntax
    * Improved README

# v1.0.1
## 19-01-2017

3. [](#bugfix)
     * Fixed caching issues

# v1.0.0
## 20-01-2017

1. [](#new)
    * Initial release
