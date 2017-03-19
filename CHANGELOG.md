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
