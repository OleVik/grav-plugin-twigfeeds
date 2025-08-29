<?php

namespace TwigFeeds\Tests;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Grav\Plugin\TwigFeedsPlugin\API\Parser;
use TwigFeeds\Tests\Utilities as Util;

// require_once 'bootstrap.php';

class RequestTest extends TestCase
{
    private $config;
    private $feeds;

    public function setUp(): void
    {
        $this->config = [
            'request_options' => [
                'allow_redirects' => true,
                'connect_timeout' => 30,
                'timeout' => 30,
                'http_errors' => false
            ],
            'pass_headers' => true,
            'log_file' => null
        ];
        $this->feeds = explode("\n", file_get_contents(__DIR__ . '/feeds.txt'));
    }

    public function testFeedsSource()
    {
        Util::output('🔨 [TEST]: Validity of sources in /tests/feeds.txt');
        foreach ($this->feeds as $feed) {
            $this->assertIsString($feed);
            $this->assertSame(1, Util::validateURL($feed));
            Util::output('✅ [Valid URL]: ' . $feed);
        }
    }

    #[Depends('testFeedsSource')]
    public function testParseFeeds()
    {
        Util::output('🔨 [TEST]: Parser-class');
        $parser = new Parser($this->config);
        $this->assertTrue($parser instanceof Parser);
        Util::output('✅ [Class]: Parser-class instantiated without errors');
        $items = [];
        foreach ($this->feeds as $feed) {
            $data['title'] = $feed;
            $data['source'] = $feed;
            $data['now'] = time();
            $data['cache'] = false;
            Util::output('🔨 [Call]: Attempt parsing feed - ' . $feed);
            $call = $parser->parseFeed($data);
            $this->assertIsArray($call, '❗ Source-call did not return an array');
            $this->assertArrayHasKey('data', $call, '❗ Array missing data-property');
            $this->assertArrayHasKey('items', $call['data'], '❗ Data missing items-property');
            $this->assertNotEmpty($call['data']['items'], '❗ Items empty');
            Util::output('✅ [feed-array has "data" with "items"]: ' . count($call['data']['items']));
            $items[$feed] = $call['data']['items'];
        }
        return $items;
    }

    #[Depends('testParseFeeds')]
    public function testFeedItems($items)
    {
        Util::output('🔨 [TEST]: feed-items');
        foreach ($items as $source => $items) {
            Util::output($source . ' items ' . count($items));
            foreach ($items as $item) {
                $this->assertArrayHasKey('title', $item, '❗ Missing title-property');
                $this->assertArrayHasKey('link', $item, '❗ Missing link-property');
                $this->assertArrayHasKey('lastModified', $item, '❗ Missing lastModified-property');
                $this->assertArrayHasKey('content', $item, '❗ Missing item-property');
                $this->assertTrue(!empty($item['content']), '❗ Empty content');
                if ($_ENV['extra']) {
                    Util::output('  ' . $item['lastModified'] . ' - ' . substr($item['title'], 0, 40) . ': ' . strlen($item['content']));
                }
            }
        }
    }
}
