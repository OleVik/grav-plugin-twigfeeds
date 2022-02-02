<?php

/**
 * TwigFeeds Plugin
 *
 * PHP version 8
 *
 * @category   Extensions
 * @package    Grav
 * @subpackage Presentation
 * @author     Ole Vik <git@olevik.net>
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @link       https://github.com/OleVik/grav-plugin-twigfeeds
 */

namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use Grav\Plugin\TwigFeedsPlugin\API\Parser;
use Grav\Plugin\TwigFeedsPlugin\API\Manifest;
use Grav\Plugin\TwigFeedsPlugin\Utilities;

/**
 * Parse RSS and Atom feeds with Twig
 *
 * Exposes RSS and Atom feeds to Grav, making them available for use in Twig.
 * This means you can define a RSS feed in the plugin-configuration, then
 * access them for iteration in Twig-templates.
 *
 * Class TwigFeedsPlugin
 *
 * @category Extensions
 * @package  Grav\Plugin
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-twigfeeds
 */
class TwigFeedsPlugin extends Plugin
{

    /**
     * Register events with Grav
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Composer autoload.
     *
     * @return \Composer\Autoload\ClassLoader
     */
    public function autoload(): \Composer\Autoload\ClassLoader
    {
        return include __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize the plugin and events
     *
     * @return void
     */
    public function onPluginsInitialized()
    {
        if (version_compare(PHP_VERSION, '8.0.0') < 0) {
            $this->grav['log']->error('TwigFeeds-plugin v5 requires PHP v8 or above');
            return;
        }
        $this->enable(
            [
                'onBeforeCacheClear' => ['onBeforeCacheClear', 0],
                'onTwigSiteVariables' => ['outputFeeds', 0],
                'onTwigPageVariables' => ['outputFeeds', 0]
            ]
        );
    }

    /**
     * Register cache-location with onBeforeCacheClear-event
     *
     * @param RocketTheme\Toolbox\Event\Event $event Event-handler
     *
     * @return void
     */
    public function onBeforeCacheClear(Event $event)
    {
        $remove = isset($event['remove']) ? $event['remove'] : 'standard';
        $paths = $event['paths'];

        if (in_array($remove, ['all', 'standard', 'cache-only']) && !in_array('cache://', $paths)) {
            $paths[] = 'cache://twigfeeds/';
            $event['paths'] = $paths;
        }
    }

    /**
     * Declare config from plugin-config
     *
     * @return array Plugin configuration
     */
    public function config()
    {
        $config = (array) $this->config->get('plugins.twigfeeds');
        $config['locator'] = $this->grav['locator'];
        $config['config_file'] = $config['locator']->findResource('user://config', true) . '/plugins/twigfeeds.yaml';
        if ($config['static_cache']) {
            $config['cache_path'] = $config['locator']->findResource('user://data', true) . '/twigfeeds/';
        } else {
            $config['cache_path'] = $config['locator']->findResource('cache://', true) . '/twigfeeds/';
        }
        $config['blueprint_path'] = $config['locator']->findResource('user://plugins/twigfeeds/blueprints.yaml', true);
        return $config;
    }

    /**
     * Logs and outputs messages to debugger
     *
     * @param string $msg Message to output
     *
     * @return string Debug messages logged and output to Debugger
     */
    protected function debug($msg)
    {
        if (is_array($msg)) {
            $this->grav['debugger']->addMessage($msg);
        } else {
            $this->grav['debugger']->addMessage('TwigFeeds: ' . $msg);
            $this->grav['log']->debug('TwigFeeds: ' . $msg);
        }
    }

    /**
     * Builds array of feeds for iteration, using cache-mechanism if enabled
     *
     * @return array Feeds
     */
    public function outputFeeds()
    {
        if ($this->isAdmin() || $this->config()['enabled'] != true) {
            return;
        }
        if (isset($this->grav['twig']->twig_vars['twig_feeds'])) {
            return;
        }
        $config = $this->config();

        $utility = new Utilities($config);
        $config['now'] = $utility->now;
        $manifest = new Manifest($config, $utility);
        $parser = new Parser($config);
        $cache = $config['cache'];
        $debug = $config['debug'];

        if ($debug && (array) $this->config->get('system.debugger.enabled')) {
            $this->grav['debugger']->startTimer('twigfeeds', 'TwigFeeds');
        }

        if ($cache) {
            /* Create Manifest */
            $manifestFile = $config['cache_path'] . 'manifest.json';

            if (!file_exists($manifestFile)) {
                $debug ? $this->debug('Manifest does not exist, writing it') : null;
                $content = $manifest->manifestStructure($config['config_file']);
                $content['data'] = $config['twig_feeds'];
                $call = $manifest->writeManifest($manifestFile, $content);
                $debug ? $this->debug($call) : null;
            } else {
                /* Test versions */
                $manifestVersion = $utility->getVersion($manifestFile, 'manifest');
                $blueprintVersion = $utility->getVersion($config['blueprint_path'], 'blueprint');
                $versionCompare = $utility->compareSemVer($manifestVersion, $blueprintVersion);
                $major = $versionCompare['compare']['major'];
                $minor = $versionCompare['compare']['minor'];
                if ($major == 'greater' || $minor == 'greater') {
                    $versionDiff = $blueprintVersion . ' != ' . $manifestVersion;
                    $debug ? $this->debug('Versions different (' . $versionDiff . '), busting cache') : null;
                    $bustCache = true;
                } else {
                    $debug ? $this->debug('Versions equal, continuing') : null;
                }

                /* Test contents of manifest and config */
                $content = $manifest->readManifest($manifestFile);
                $call = $manifest->compare($content);
                if ($call['state'] == 'changed') {
                    $callback = $call['state'];
                    $debug ? $this->debug('Config (' . $callback . ') and manifest unequal, busting cache') : null;
                    $bustCache = true;
                } else {
                    $debug ? $this->debug('Config and manifest equal, continuing') : null;
                }

                /* If necessary, bust cache */
                if (isset($bustCache)) {
                    $call = $manifest->bustCache();
                    $debug ? $this->debug($call) : null;

                    /* Update manifest */
                    $debug ? $this->debug('Updating manifest') : null;
                    $content = $manifest->manifestStructure($manifestFile);
                    $call = $manifest->writeManifest($manifestFile, $content);
                    $debug ? $this->debug($call) : null;
                }
            }

            /* Parse feeds */
            $content = $manifest->readManifest($manifestFile);
            foreach ($content['data'] as $entry => $data) {
                $data['title'] = $data['filename'];
                $data['source'] = $entry;
                $data['now'] = $utility->now;
                $data['cache'] = true;
                if (!$config['pass_headers']) {
                    unset($data['etag']);
                    unset($data['last_modified']);
                }
                $path = $config['cache_path'] . $data['filename'];
                if (!file_exists($path)) {
                    $debug ? $this->debug('Can\'t find ' . $data['filename'] . ', writing it') : null;
                    $call = $parser->parseFeed($data, $path);
                    if ($config['silence_security'] && $call == null) {
                        continue;
                    }
                    if ($debug && isset($call['callback'])) {
                        $this->debug($call['callback']);
                    }
                    if (isset($call['data']['etag'])) {
                        $content['data'][$entry]['etag'] = $call['data']['etag'];
                    }
                    if (isset($call['data']['last_modified'])) {
                        $content['data'][$entry]['last_modified'] = $call['data']['last_modified'];
                    }
                    $content['data'][$entry]['last_checked'] = $utility->now;
                    $content['data'][$entry]['last_checked_date'] = $utility->humanDate($utility->now);
                } else {
                    $then = $utility->humanDate($data['last_checked'] + $data['cache_time']);
                    $now = $utility->humanDate($utility->now);
                    if (($data['last_checked'] + $data['cache_time']) > $utility->now) {
                        $debug ? $this->debug($then . ' is after ' . $now . ', skip ' . $entry) : null;
                    } else {
                        $debug ? $this->debug($then . ' is before ' . $now . ', download ' . $entry) : null;
                        $call = $parser->parseFeed($data, $path);
                        if ($config['silence_security'] && $call == null) {
                            continue;
                        }
                        if ($debug && isset($call['callback'])) {
                            $this->debug($call['callback']);
                        }
                        if (isset($call['data']['etag'])) {
                            $content['data'][$entry]['etag'] = $call['data']['etag'];
                        }
                        if (isset($call['data']['last_modified'])) {
                            $content['data'][$entry]['last_modified'] = $call['data']['last_modified'];
                        }
                        $content['data'][$entry]['last_checked'] = $utility->now;
                        $content['data'][$entry]['last_checked_date'] = $utility->humanDate($utility->now);
                    }
                }
            }
            $debug ? $this->debug('Updating manifest') : null;
            $call = $manifest->updateManifest($manifestFile, $content);
            $debug ? $this->debug($call) : null;
        }

        /* Read data into Twig-variable */
        $feed_items = array();
        if ($cache) {
            foreach ($content['data'] as $source => $data) {
                $filename = $config['cache_path'] . $data['filename'];
                $content = $parser->readFeed($filename);
                if ($content) {
                    $feed_items[$content['name']] = $content;
                } else {
                    $debug ? $this->debug('Could not find ' . $filename . ', continuing') : null;
                }
            }
        } else {
            foreach ($config['twig_feeds'] as $feed) {
                $feed['now'] = $utility->now;
                $feed['cache'] = 'no';
                if (isset($feed['start'])) {
                    $start = $feed['start'];
                } else {
                    $start = 0;
                }
                if (isset($feed['end'])) {
                    $end = $feed['end'];
                } else {
                    $end = 50;
                }
                $feed['amount'] = abs($start - $end);
                $resource = $parser->parseFeed($feed);
                if ($config['silence_security'] && $resource == null) {
                    continue;
                }
                if (isset($feed['name'])) {
                    $name = $feed['name'];
                } elseif (isset($resource['data']['title'])) {
                    $name = $resource['data']['title'];
                } else {
                    $name = $feed['source'];
                }
                $feed_items[$name] = $feed;
                if (isset($resource['data']) && !empty($resource['data'])) {
                    $feed_items[$name] = array_merge($feed, $resource['data']);
                }
                $debug ? $this->debug($resource['data']) : null;
            }
        }
        $this->grav['twig']->twig_vars['twig_feeds'] = $feed_items;
        if ($debug && (array) $this->config->get('system.debugger.enabled')) {
            $this->grav['debugger']->stopTimer('twigfeeds');
        }
    }
}
