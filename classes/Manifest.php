<?php

/**
 * TwigFeeds Plugin, Manifest API
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

namespace Grav\Plugin\TwigFeedsPlugin\API;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * TwigFeeds Manifest
 *
 * Class Manifest
 *
 * @category Extensions
 * @package  Grav\Plugin\TwigFeedsPlugin
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-twigfeeds
 */
class Manifest
{

    /**
     * True if cache is enabled
     *
     * @var bool
     */
    public $cache;

    /**
     * True if static cache is enabled
     *
     * @var bool
     */
    public $staticCache;

    /**
     * True if debug is enabled
     *
     * @var bool
     */
    public $debug;

    /**
     * Path to config-file
     *
     * @var string
     */
    public $configFile;

    /**
     * Path to cache-folder
     *
     * @var string
     */
    public $cachePath;

    /**
     * Time to wait before caching data again
     *
     * @var int
     */
    public $cacheTime;

    /**
     * Plugin version from blueprint.yaml
     *
     * @var string
     */
    public $blueprintVersion;

    /**
     * True if ETag and last Modified should be passed when caching
     *
     * @var bool
     */
    public $passHeaders;

    /**
     * TwigFeeds-plugin 'twig_feeds'-configuration
     *
     * @var array
     */
    public $twigFeeds;

    /**
     * Symfony Filesystem Component
     *
     * @var Filesystem
     */
    public $filesystem;

    /**
     * Instantiate TwigFeeds Manifest
     *
     * @param array     $config  TwigFeeds-plugin configuration
     * @param Utilities $utility Utilities-instance
     */
    public function __construct($config, $utility)
    {
        $this->utility = $utility;
        $this->cache = $config['cache'];
        $this->staticCache = $config['static_cache'];
        $this->debug = $config['debug'];
        $this->configFile = $config['config_file'];
        $this->cachePath = $config['cache_path'];
        $this->blueprintVersion = $utility->getVersion($config['blueprint_path'], 'blueprint');
        if (isset($config['cache_time'])) {
            $this->cacheTime = $config['cache_time'];
        } else {
            $this->cacheTime = 900;
        }
        $this->passHeaders = $config['pass_headers'];
        $this->twigFeeds = $config['twig_feeds'];
        $this->filesystem = new Filesystem();
    }

    /**
     * Clears caches
     *
     * @param array $paths Paths to caches to clear
     *
     * @return string Status of operation
     *
     * @throws IOException If Symfony Filesystem remove() fails
     */
    public function bustCache($paths = false)
    {
        if (!$paths) {
            $paths = array($this->cachePath);
        }
        if (!is_array($paths)) {
            $paths = array($paths);
        }
        foreach ($paths as $path) {
            if (is_dir($path)) {
                try {
                    $this->filesystem->remove($path);
                } catch (IOExceptionInterface $e) {
                    throw new \Exception($e);
                }
                return 'Removed ' . $path;;
            } else {
                return 'Not a directory: ' . $path;
            }
        }
    }

    /**
     * Read manifest
     *
     * @param string $file Path to manifest-file
     *
     * @return array Decoded JSON
     */
    public function readManifest($file)
    {
        if (file_exists($file)) {
            $manifest = file_get_contents($file);
        } else {
            $manifest = $this->manifestStructure($file);
        }
        return json_decode($manifest, true);
    }

    /**
     * Build manifest
     *
     * @param array $manifestFile Path to manifest-file
     * @param array $feeds        Manifest-data
     *
     * @return string Status of operation
     *
     * @throws IOException If Symfony Filesystem dumpFile() fails
     */
    public function writeManifest($manifestFile, $feeds)
    {
        $manifest = array();
        $manifest['config'] = $feeds['config'];
        foreach ($this->twigFeeds as $feed) {
            $manifest['data'][$feed['source']]['filename'] = hash('md5', $feed['source']) . '.json';
            if (isset($feed['name'])) {
                $manifest['data'][$feed['source']]['name'] = $feed['name'];
            }
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
            $amount = abs($start - $end);
            $manifest['data'][$feed['source']]['amount'] = $amount;
            $manifest['data'][$feed['source']]['etag'] = '';
            $manifest['data'][$feed['source']]['last_modified'] = '';
            if (isset($feed['cache_time'])) {
                $manifest['data'][$feed['source']]['cache_time'] = $feed['cache_time'];
            } elseif (isset($setting['cache_time'])) {
                $manifest['data'][$feed['source']]['cache_time'] = $setting['cache_time'];
            } else {
                $manifest['data'][$feed['source']]['cache_time'] = $this->cacheTime;
            }
            if (isset($feed['extra_tags'])) {
                $manifest['data'][$feed['source']]['extra_tags'] = $feed['extra_tags'];
            }
            $manifest['data'][$feed['source']]['last_checked'] = $this->utility->now;
            $manifest['data'][$feed['source']]['last_checked_date'] = $this->utility->humanDate($this->utility->now);
        }
        $manifest = json_encode($manifest, JSON_PRETTY_PRINT);
        try {
            $this->filesystem->dumpFile($manifestFile, $manifest);
            return 'Built ' . $manifestFile;
        } catch (IOException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Update manifest
     *
     * @param array $manifestFile Path to manifest-file
     * @param array $manifest     Manifest-data
     *
     * @return string Status of operation
     *
     * @throws IOException If Symfony Filesystem dumpFile() fails
     */
    public function updateManifest($manifestFile, $manifest)
    {
        try {
            $this->filesystem->dumpFile($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));
            return 'Updated ' . $manifestFile;
        } catch (IOException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Create manifest structure
     *
     * @param string $file Path to manifest-file
     *
     * @return array Manifest structure
     */
    public function manifestStructure($file)
    {
        if (file_exists($file)) {
            $manifest = array(
                'config' => array(
                    'user' => true,
                    'modified' => time(),
                    'modified_date' => $this->utility->humanDate(time())
                ),
                'data' => array()
            );
        } else {
            $manifest = array(
                'config' => array(
                    'user' => false
                ),
                'data' => array()
            );
        }
        $manifest['config']['version'] = $this->blueprintVersion;
        return $manifest;
    }

    /**
     * Compare manifest and settings
     *
     * @param array $manifest Manifest
     *
     * @return array 'state' for state of operation
     */
    public function compare($manifest)
    {
        foreach (array_keys($manifest['data']) as $key) {
            unset($manifest['data'][$key]['etag']);
            unset($manifest['data'][$key]['last_modified']);
            unset($manifest['data'][$key]['last_checked']);
            unset($manifest['data'][$key]['last_checked_date']);
        }
        $feeds = array();
        foreach ($this->twigFeeds as $feed) {
            $feeds[$feed['source']]['filename'] = hash('md5', $feed['source']) . '.json';
            if (isset($feed['name'])) {
                $feeds[$feed['source']]['name'] = $feed['name'];
            }
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
            $feeds[$feed['source']]['amount'] = abs($start - $end);
            if (isset($feed['cache_time'])) {
                $feeds[$feed['source']]['cache_time'] = $feed['cache_time'];
            } elseif (isset($setting['cache_time'])) {
                $feeds[$feed['source']]['cache_time'] = $setting['cache_time'];
            } else {
                $feeds[$feed['source']]['cache_time'] = $this->cacheTime;
            }
            if (isset($feed['extra_tags'])) {
                $feeds[$feed['source']]['extra_tags'] = $feed['extra_tags'];
            }
        }
        $return = array('state' => '');
        if ($manifest['data'] == $feeds) {
            $return['state'] = 'unchanged';
        } else {
            $return['state'] = 'changed';
        }
        return $return;
    }
}
