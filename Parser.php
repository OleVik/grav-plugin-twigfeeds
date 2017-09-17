<?php

namespace TwigFeeds;

use DateTime;

require __DIR__ . '/vendor/autoload.php';
use PicoFeed\Reader\Reader;
use PicoFeed\Config\Config;
use PicoFeed\PicoFeedException;
use PicoFeed\Client\InvalidCertificateException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * TwigFeeds Parser
 *
 * Class Parser
 * 
 * @package Grav\Plugin\TwigFeedsPlugin
 * @license MIT License by Ole Vik
 * @since   v3.0.0
 */
class Parser
{

    /**
     * Symfony Filesystem Component
     * 
     * @var Filesystem
     */
    public $filesystem;

    /**
     * Instantiate TwigFeeds Parser
     */
    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * Read feed
     * 
     * @param string $file Path to manifest
     * 
     * @return array Decoded JSON
     */
    public function readFeed($file)
    {
        $feed = file_get_contents($file);
        $json = json_decode($feed, true);
        return $json;
    }

    /**
     * Parse and write feed
     * 
     * @param array $args Feed settings
     * @param array $path Path JSON filename
     * 
     * @throws PicoFeedException If PicoFeed Reader fails
     * @throws IOException If Symfony Filesystem dumpFile fails
     * @throws Exception For other errors
     * 
     * @return array Structured feed
     */
    public function parseFeed($args, $path = false)
    {
        $data = array();
        try {
            $config = new Config;
            $config->setTimezone('UTC');
            $reader = new Reader($config);

            try {
                if (!empty($args['last_modified']) && !empty($args['etag'])) {
                    $resource = $reader->download($args['source'], $args['last_modified'], $args['etag']);
                } else {
                    $resource = $reader->download($args['source']);
                }
            } catch (InvalidCertificateException $e) {
                if ($config['silence_security'] === false) {
                    throw new \Exception($e);
                }
            }

            if ($resource->isModified()) {
                $parser = $reader->getParser(
                    $resource->getUrl(),
                    $resource->getContent(),
                    $resource->getEncoding()
                );
                $result = $parser->execute();
                $title = $result->getTitle();
            } else {
                $title = $args['title'];
            }
            /* Fallback */
            if (!isset($result)) {
                $parser = $reader->getParser(
                    $resource->getUrl(),
                    $resource->getContent(),
                    $resource->getEncoding()
                );
                $result = $parser->execute();
                $title = $result->getTitle();
            }
            $etag = $resource->getEtag();
            $last_modified = $resource->getLastModified();
            if (!empty($last_modified)) {
                $dateObject = DateTime::createFromFormat(DateTime::RSS, $last_modified);
            } else {
                $dateObject = new DateTime('now');
            }
            $timestamp = $dateObject->getTimestamp();

            $data['title'] = $title;
            if (isset($args['name'])) {
                $data['name'] = $args['name'];
            } else {
                $data['name'] = $title;
            }
            $data['etag'] = $etag;
            $data['last_modified'] = $last_modified;
            $data['timestamp'] = $timestamp;
            $data['last_checked'] = $args['now'];
            $data['amount'] = $args['amount'];
            $data['items'] = array();
            $int = 0;
            foreach ($result->items as $key => $item) {
                $return = (array) $item;
                $data['items'][$key] = $return;
                if (isset($args['extra_tags'])) {
                    foreach ($args['extra_tags'] as $extra) {
                        if (!is_array($extra)) {
                            $data['items'][$key][$extra] = $item->getTag($extra);
                        } else {
                            foreach ($extra as $tag => $attributes) {
                                foreach ($attributes as $attribute) {
                                    $data['items'][$key][$tag][$attribute] = $item->getTag($tag, $attribute);
                                }
                            }
                        }
                    }
                }
                if (++$int >= $args['amount']) {
                    break;
                }
            }
        } catch (PicoFeedException $e) {
            throw new \Exception($e);
        } catch (Exception $e) {
            throw new \Exception($e);
        }
        $return = array();
        if ($args['cache'] === true) {
            if (empty($path)) {
                throw new \Exception('Parser->parseFeed() has no path');
            } else {
                try {
                    $this->filesystem->dumpFile($path, json_encode($data, JSON_PRETTY_PRINT));
                    $return['callback'] = 'Wrote ' . $path;
                } catch (IOException $e) {
                    throw new \Exception($e);
                }
            }
        }
        $return['data'] = $data;
        return $return;
    }
}
