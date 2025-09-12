<?php

/**
 * TwigFeeds Plugin, Utilities API
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

namespace Grav\Plugin\TwigFeedsPlugin;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Yaml\Yaml;
use Naneau\SemVer\Parser as SemVerParser;
use Naneau\SemVer\Compare as SemVerCompare;

/**
 * TwigFeeds Utilities
 *
 * Class Utilities
 *
 * @category Extensions
 * @package  Grav\Plugin\TwigFeedsPlugin
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-twigfeeds
 */
class Utilities
{

    /**
     * Current time as timestamp.
     *
     * @var int
     */
    public $now;

    /**
     * Path to cache-folder
     *
     * @var string
     */
    public $cachePath;

    /**
     * Symfony Filesystem Component
     *
     * @var Filesystem
     */
    public $fs;

    /**
     * Instantiate TwigFeeds Utilities
     *
     * @param array $config TwigFeeds-plugin configuration
     */
    public function __construct($config)
    {
        $this->now = time();
        $this->cachePath = $config['cache_path'];
        $this->fs = new Filesystem();
    }

    /**
     * Formats timestamp as a human-readable date
     *
     * @param int $timestamp Unix timestamp
     *
     * @return string Date in 'Y-M-d H:i:s' format
     */
    public function humanDate($timestamp)
    {
        return date("Y-m-d H:i:s", $timestamp);
    }

    /**
     * Clears caches
     *
     * @param array $paths Paths to caches
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
                    $this->fs->remove($path);
                } catch (IOException $e) {
                    throw new \Exception($e);
                }
                return 'Removed ' . $path;
            } else {
                return 'Not a directory: ' . $path;
            }
        }
    }

    /**
     * Fetch the plugin version from the blueprints or manifest
     *
     * @param string $path Path to file
     * @param string $mode 'blueprint' or 'manifest'
     *
     * @return string Retrieved version
     */
    public function getVersion($path, $mode = 'blueprint')
    {
        if ($mode == 'blueprint') {
            $blueprint = file_get_contents($path);
            $data = Yaml::parse($blueprint);
            return $data['version'];
        } elseif ($mode == 'manifest') {
            $manifest = file_get_contents($path);
            $json = json_decode($manifest, true);
            return $json['config']['version'];
        }
    }

    /**
     * Compares two semantic versions
     *
     * @param string $old Old version, in SemVer format
     * @param string $new New version, in SemVer format
     *
     * @return array Comparative version information
     */
    public function compareSemVer($old, $new)
    {
        $versions = array();
        $versions['old']['version'] = $old;
        $versions['new']['version'] = $new;

        $old = SemVerParser::parse($old);
        $versions['old']['major'] = $old->getMajor();
        $versions['old']['minor'] = $old->getMinor();
        $versions['old']['patch'] = $old->getPatch();
        if ($old->hasPreRelease()) {
            $versions['old']['preRelease'] = $old->getPreRelease()->getGreek();
            $versions['old']['number'] = $old->getPreRelease()->getReleaseNumber();
        }

        $new = SemVerParser::parse($new);
        $versions['new']['major'] = $new->getMajor();
        $versions['new']['minor'] = $new->getMinor();
        $versions['new']['patch'] = $new->getPatch();
        if ($new->hasPreRelease()) {
            $versions['new']['preRelease'] = $new->getPreRelease()->getGreek();
            $versions['new']['number'] = $new->getPreRelease()->getReleaseNumber();
        }

        switch (true) {
            case ($versions['new']['major'] > $versions['old']['major']):
                $versions['compare']['major'] = 'greater';
                break;
            case ($versions['new']['major'] == $versions['old']['major']):
                $versions['compare']['major'] = 'equal';
                break;
            case ($versions['new']['major'] < $versions['old']['major']):
                $versions['compare']['major'] = 'smaller';
                break;
        }
        switch (true) {
            case ($versions['new']['minor'] > $versions['old']['minor']):
                $versions['compare']['minor'] = 'greater';
                break;
            case ($versions['new']['minor'] == $versions['old']['minor']):
                $versions['compare']['minor'] = 'equal';
                break;
            case ($versions['new']['minor'] < $versions['old']['minor']):
                $versions['compare']['minor'] = 'smaller';
                break;
        }
        switch (true) {
            case ($versions['new']['patch'] > $versions['old']['patch']):
                $versions['compare']['patch'] = 'greater';
                break;
            case ($versions['new']['patch'] == $versions['old']['patch']):
                $versions['compare']['patch'] = 'equal';
                break;
            case ($versions['new']['patch'] < $versions['old']['patch']):
                $versions['compare']['patch'] = 'smaller';
                break;
        }

        if ($old->hasPreRelease() && $new->hasPreRelease()) {
            if (SemVerCompare::greaterThan($old, $new)) {
                $versions['compare']['preRelease'] = 'greater';
            } elseif (SemVerCompare::equals($old, $new)) {
                $versions['compare']['preRelease'] = 'equal';
            } elseif (SemVerCompare::smallerThan($old, $new)) {
                $versions['compare']['preRelease'] = 'smaller';
            }
        }

        return $versions;
    }

    /**
     * Try to normalize XML feed-data into a common structure
     *
     * @param array $data Input-data
     *
     * @return array
     */
    public static function normalizeDirectFeedData($data)
    {
        if (array_key_exists('channel', $data)) {
            $data = $data['channel'];
        } else {
            return $data;
        }
        if (
            !array_key_exists('items', $data) &&
            array_key_exists('entry', $data)
        ) {
            $data['items'] = $data['entry'];
            unset($data['entry']);
        }
        if (
            !array_key_exists('items', $data) &&
            array_key_exists('item', $data)
        ) {
            $data['items'] = $data['item'];
            unset($data['item']);
        }
        foreach ($data['items'] as $item) {
          if (array_key_exists('item', $data)) {
              $data['items'] = $data['item'];
              unset($data['item']);
          }
        }
        for ($i=0; $i < count($data['items']); $i++) {
            if (
              !array_key_exists('content', $data['items'][$i]) &&
              array_key_exists('description', $data['items'][$i])
            ) {
                $data['items'][$i]['content'] = $data['items'][$i]['description'];
                unset($data['items'][$i]['description']);
            }
            if (
              !array_key_exists('lastModified', $data['items'][$i]) &&
              array_key_exists('pubDate', $data['items'][$i])
            ) {
                $data['items'][$i]['lastModified'] = $data['items'][$i]['pubDate'];
                unset($data['items'][$i]['pubDate']);
            }
        }
        return $data;
    }

    /**
     * Recursively convert XML-data to a normal array
     *
     * @param SimpleXMLElement $xml XML-data
     *
     * @return array
     *
     * @see https://stackoverflow.com/a/24919807
     */
    public static function simpleXml2ArrayWithCDATASupport($xml)
    {
        $array = (array) $xml;
        if (count($array) === 0) {
            return (string)$xml;
        }
        foreach ($array as $key => $value) {
            if (is_object($value) && strpos(get_class($value), 'SimpleXML') > -1) {
                $array[$key] = self::simpleXml2ArrayWithCDATASupport($value);
            } else if (is_array($value)) {
                $array[$key] = self::simpleXml2ArrayWithCDATASupport($value);
            } else {
                continue;
            }
        }
        return $array;
    }
}
