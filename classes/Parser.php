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
use FeedIo\Adapter\Guzzle\Client;
use FeedIo\Reader\ReadErrorException;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Log\NullLogger;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Grav\Common\Utils;

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
     * Parser configuration
     */
    public $config;

    /**
     * Logger-instance
     *
     * @var NullLogger|Logger
     */
    public $logger;

    /**
     * Instantiate TwigFeeds Parser
     *
     * @param array $config Plugin-configuration
     */
    public function __construct($config)
    {
        $this->filesystem = new Filesystem();
        $this->config = $config;
        $this->logger = new NullLogger();
        if ($this->config['log_file'] && !empty($this->config['log_file']) && is_string($this->config['log_file'])) {
            $this->logger = new Logger('default', [new StreamHandler($this->config['log_file'])]);
        }
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
     * @return array Structured feed
     *
     * @throws IOException If Symfony Filesystem dumpFile fails
     * @throws TimeoutException In case of a timeout
     * @throws Exception For other errors
     */
    public function parseFeed($args, $path = false)
    {
        $data = array();
        $requestOptions = $this->config['request_options'];
        $mode = 'default';
        if (isset($args['mode']) && $args['mode'] === 'direct') {
            $mode = 'direct';
        }
        if (isset($args['request_options'])) {
            $requestOptions = Utils::arrayMergeRecursiveUnique($this->config['request_options'], $args['request_options']);
        }
        try {
            $resource = Parser::query($args['source'], $requestOptions, $this->logger, $mode);
            if ($mode === 'direct') {
                return simplexml_load_string($resource->getBody());
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
        if ($resource === null) {
            $this->logger->debug('Querying ' . $args['source'] . ' returned null or failed.');
            return;
        }
        $feed = $resource->getFeed();
        if (count($feed->toArray()['items']) < 1) {
            return;
        }
        if ($this->config['pass_headers'] == true) {
            if (isset($args['etag']) && !empty($args['etag'])) {
                if ($args['etag'] === $resource->getResponse()->getHeaders()['ETag'][0]) {
                    return;
                }
            }
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
        if (!empty($feed->getTitle())) {
            $data['title'] = $feed->getTitle();
        } else {
            $data['title'] = $args['title'];
        }
        if (isset($args['name'])) {
            $data['name'] = $args['name'];
        } elseif (isset($args['title'])) {
            $data['name'] = $args['title'];
        }
        $data['last_modified'] = $lastModified;
        $data['timestamp'] = $timestamp;
        $data['last_checked'] = $args['now'];
        if (isset($args['amount'])) {
            $amount = $args['amount'];
        } else {
            $amount = 50;
        }
        $data['items'] = array();
        $int = 0;
        foreach ($feed->toArray()['items'] as $item) {
            $item['lastModified'] = self::getItemDate($item, $lastModified->format('c'));
            $data['items'][] = $item;
            if (++$int >= $amount) {
                break;
            }
        }
        $return = array();
        if ($args['cache'] === true) {
            if (empty($path)) {
                throw new \Exception('Parser->parseFeed() has no path');
            } else {
                try {
                    $this->filesystem->dumpFile($path, json_encode($data));
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
     * Query remote resources
     *
     * @param string $URL Target source
     * @param array $requestOptions Guzzle Client-options
     * @param NullLogger|Logger $logger Logger-instance
     * @param string $mode Operating mode, either 'default' or 'direct'
     *
     * @return object|null Query-result or null
     *
     * @throws \FeedIo\Reader\ReadErrorException If runtime-error
     * @throws \GuzzleHttp\Exception If connection-error
     * @throws \Exception For other errors
     */
    public static function query($URL, $requestOptions, $logger, $mode = 'default')
    {
        $guzzle = new GuzzleClient($requestOptions);
        $client = new Client($guzzle);
        $resource = null;
        try {
            if ($mode === 'default' || empty($mode)) {
                $feedIo = new \FeedIo\FeedIo($client, $logger);
                try {
                    $resource = $feedIo->read($URL);
                } catch (ReadErrorException $e) {
                    error_log($e);
                    $logger->error($e);
                }
            } elseif ($mode === 'direct') {
                $resource = $client->request('GET', $URL);
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            error_log($e);
            $logger->error($e);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            error_log($e);
            $logger->error($e);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            error_log($e);
            $logger->error($e);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            error_log($e);
            $logger->error($e);
        } catch (\GuzzleHttp\Exception\TooManyRedirectsException $e) {
            error_log($e);
            $logger->error($e);
        } catch (\Exception $e) {
            error_log($e);
            $logger->error($e);
            throw new \Exception($e);
        }
        return $resource;
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
