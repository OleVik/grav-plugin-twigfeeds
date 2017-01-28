<?php
namespace Grav\Plugin;

use Grav\Common\Data;
use Grav\Common\Plugin;
use Grav\Common\Grav;
use Grav\Common\Uri;
use Grav\Common\Taxonomy;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\File\File;
require __DIR__ . '/vendor/autoload.php';
use PicoFeed\Reader\Reader;
use PicoFeed\Config\Config;
use PicoFeed\PicoFeedException;

class TwigFeedsPlugin extends Plugin
{
	public static function getSubscribedEvents() {
		return [
			'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
		];
	}
	public function onTwigSiteVariables(Event $event) {
		if (!$this->isAdmin()) {
			$pluginsobject = (array) $this->config->get('plugins');
			$pluginsobject = $pluginsobject['twigfeeds'];
			if (isset($pluginsobject) && $pluginsobject['enabled']) {
				if (is_array($pluginsobject['twig_feeds'])) {
					$items = array();
					foreach ($pluginsobject['twig_feeds'] as $feed) {
						try {
							$config = new Config;
							$config->setTimezone('UTC');
							$reader = new Reader($config);
							$resource = $reader->download($feed['source']);
							$parser = $reader->getParser(
								$resource->getUrl(),
								$resource->getContent(),
								$resource->getEncoding()
							);
							$result = $parser->execute();
							$title = $result->getTitle();
							$source = $feed['source'];
							
							if (isset($feed['name'])) {
								$name = $feed['name'];
							} else {
								$name = $title;
							}
							if (isset($feed['start'])) {
								$start = $feed['start'];
							} else {
								$start = 0;
							}
							if (isset($feed['end'])) {
								$end = $feed['end'];
							} else {
								$end = 500;
							}
							if (isset($feed['start']) && isset($feed['end'])) {
								$amount = abs($start-$end);
							} else {
								$amount = count($result->items);
							}
							
							$items[$name]['title'] = $title;
							$items[$name]['source'] = $source;
							$items[$name]['start'] = $start;
							$items[$name]['end'] = $end;
							$items[$name]['amount'] = $amount;
							foreach ($result->items as $item) {
								$items[$name]['items'][] = $item;
								if (++$start == $end) break;
							}
						}
						catch (PicoFeedException $e) {
							$this->grav['debugger']->addMessage('PicoFeed threw an exception: ' . $e);
						}
						catch (Exception $e) {
							$this->grav['debugger']->addMessage('Twig Feeds-plugin threw an exception: ' . $e);
						}
					}
					$this->grav['twig']->twig_vars['twig_feeds'] = $items;
				}
			}
		}
	}
}