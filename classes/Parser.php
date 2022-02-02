<?php

/**
 * TwigFeeds Plugin, Parser API
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

use DateTime;
use DateTimeZone;
use FeedIo\Adapter\Guzzle\Client;
use FeedIo\Reader\ReadErrorException;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

/**
 * TwigFeeds Parser
 *
 * Class Parser
 *
 * @category Extensions
 * @package  Grav\Plugin\TwigFeedsPlugin
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-twigfeeds
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
     *
     * @param array $config Plugin-configuration
     */
    public function __construct($config)
    {
        $this->filesystem = new Filesystem();
        $this->config = $config;
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
        if (!file_exists($file)) {
            return false;
        }
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
     * @throws IOException If Symfony Filesystem dumpFile fails
     * @throws TimeoutException In case of a timeout
     * @throws Exception For other errors
     *
     * @return array Structured feed
     */
    public function parseFeed($args, $path = false)
    {
        $data = array();
        try {
            $guzzle = new GuzzleClient($this->config['request_options']);
            $client = new Client($guzzle);
            $logger = new NullLogger();
            $feedIo = new \FeedIo\FeedIo($client, $logger);
            try {
                if ($this->config['pass_headers'] == true && !empty($args['last_modified'])) {
                    $resource = $feedIo->readSince(
                        $args['source'],
                        new \DateTime(
                            $args['last_modified']['date'],
                            new DateTimeZone($args['last_modified']['timezone'])
                        )
                    );
                } else {
                    $resource = $feedIo->read(
                        $args['source']
                    );
                }
            } catch (InvalidCertificateException $e) {
                if ($this->config['silence_security'] != true) {
                    throw new \Exception($e);
                }
            } catch (ReadErrorException $e) {
                error_log($e);
                return array();
            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                error_log($e);
                return array();
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                error_log($e);
                return array();
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                error_log($e);
                return array();
            } catch (\GuzzleHttp\Exception\ServerException $e) {
                error_log($e);
                return array();
            } catch (\GuzzleHttp\Exception\TooManyRedirectsException $e) {
                error_log($e);
                return array();
            } catch (Exception $e) {
                throw new \Exception($e);
            }

            if ($this->config['pass_headers'] == true) {
                if (isset($args['etag']) && !empty($args['etag'])) {
                    if ($args['etag'] === $resource->getResponse()->getHeaders()['ETag'][0]) {
                        return;
                    }
                }
            }
            $result = $resource->getFeed();
            if (count($result->toArray()['items']) < 1) {
                return;
            }

            if (!empty($resource->getResponse()->getLastModified())) {
                $lastModified = $resource->getResponse()->getLastModified();
            } else {
                $lastModified = new DateTime('now');
            }
            $timestamp = $lastModified->getTimestamp();
            if (isset($resource->getResponse()->getHeaders()['ETag'])) {
                $data['etag'] = $resource->getResponse()->getHeaders()['ETag'][0];
            }
            if (!empty($result->getTitle())) {
                $data['title'] = $result->getTitle();
            } else {
                $data['title'] = $args['title'];
            }
            if (isset($args['name'])) {
                $data['name'] = $args['name'];
            } else {
                $data['name'] = $args['title'];
            }
            $data['last_modified'] = $lastModified;
            $data['timestamp'] = $timestamp;
            $data['last_checked'] = $args['now'];
            $data['amount'] = $args['amount'];
            $data['items'] = array();
            $int = 0;
            foreach ($result->toArray()['items'] as $item) {
                $item['lastModified'] = self::getItemDate($item, $lastModified->format('c'));
                $data['items'][] = $item;
                if (++$int >= $args['amount']) {
                    break;
                }
            }
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

    /**
     * Find Item date
     *
     * @param array $item         Feed Item
     * @param int   $lastModified Feed Modified date
     *
     * @return int Modified date
     */
    public static function getItemDate($item, $lastModified)
    {
        if (isset($item['lastModified']) && !empty($item['lastModified'])) {
            return $item['lastModified'];
        } elseif (isset($item['elements']['dc:date']) && !empty($item['elements']['dc:date'])) {
            return $item['elements']['dc:date'];
        } elseif (isset($lastModified) && !empty($lastModified)) {
            return $lastModified;
        }
        return null;
    }
}
