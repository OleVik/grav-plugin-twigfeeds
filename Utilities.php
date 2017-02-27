<?php
namespace TwigFeeds;

require __DIR__ . '/vendor/autoload.php';
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * TwigFeeds Helper Utilities
 *
 * Class Utilities
 * @package Grav\Plugin\TwigFeedsPlugin
 * @license MIT License by Ole Vik
 * @since v3.0.0
 */
class Utilities
{

    /**
     * Current time as timestamp.
     * @var int
     */
    public $now;

    /**
     * Path to cache-folder
     * @var string
     */
    public $cachePath;

    /**
     * Symfony Filesystem Component
     * @var Filesystem
     */
    public $fs;

    /**
     * Instantiate TwigFeeds Utilities
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
     * @param int $timestamp Unix timestamp
     * @return string Date in 'Y-M-d H:i:s' format
     */
    public function humanDate($timestamp)
    {
        return date("Y-m-d H:i:s", $timestamp);
    }

    /**
     * Clears caches
     * @param array $paths Paths to caches
     * @return string Status of operation
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
                } catch (IOExceptionInterface $e) {
                    throw new \Exception($e);
                }
                return 'Removed ' . $path;
                ;
            } else {
                return 'Not a directory: ' . $path;
            }
        }
    }
}
