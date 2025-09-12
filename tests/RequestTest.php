<?php

namespace TwigFeeds\Tests;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Grav\Plugin\TwigFeedsPlugin\API\Parser;
use TwigFeeds\Tests\Utilities as Util;

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
                'http_errors' => true
            ],
            'pass_headers' => true,
            'log_file' => null
        ];
        $this->feeds = json_decode(file_get_contents(__DIR__ . '/feeds.json'));
    }

    public function testFeedsSource()
    {
        Util::output('🔨 [TEST]: Validity of sources in /tests/feeds.json');
        foreach ($this->feeds as $feed) {
            $this->assertIsString($feed->source);
            $this->assertSame(1, Util::validateURL($feed->source));
            Util::output('✅ [Valid URL]: ' . $feed->source);
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
            $data['title'] = $feed->title ?? $feed->source;
            $data['source'] = $feed->source;
            $data['now'] = time();
            $data['cache'] = false;
            $data['mode'] = $feed->mode ?? 'default';
            Util::output('🔨 [Call]: Attempt parsing feed - ' . $feed->source);
            $call = $parser->parseFeed($data);
            $this->assertIsArray($call, '❗ Source-call did not return an array');
            $this->assertArrayHasKey('data', $call, '❗ Array missing data-property');
            $this->assertArrayHasKey('items', $call['data'], '❗ Data missing items-property');
            $this->assertNotEmpty($call['data']['items'], '❗ Items empty');
            Util::output('✅ [feed-array has "data" with "items"]: ' . count($call['data']['items']));
            $items[$feed->source] = $call['data']['items'];
        }
        return $items;
    }

    #[Depends('testParseFeeds')]
    public function testFeedItems($items)
    {
        Util::output('🔨 [TEST]: feed-items');
        foreach ($items as $source => $items) {
            if ($_ENV['extra']) {
                Util::output('🔍 First ' . $_ENV['extra_limit'] .
                ' items of ' . $source);
            }
            foreach (array_slice($items, 0, $_ENV['extra_limit'], true) as $item) {
                $this->assertArrayHasKey('title', $item, '❗ Missing title-property');
                $this->assertArrayHasKey('link', $item, '❗ Missing link-property');
                $this->assertArrayHasKey('lastModified', $item, '❗ Missing lastModified-property');
                $this->assertArrayHasKey('content', $item, '❗ Missing item-property');
                $this->assertTrue(!empty($item['content']), '❗ Empty content');
                if ($_ENV['extra']) {
                    Util::output(
                        '   ' . $item['lastModified'] .
                        ' ' . substr($item['title'], 0, 40) .
                        ' (' . strlen($item['content']) . ')'
                    );
                }
            }
        }
    }
}
