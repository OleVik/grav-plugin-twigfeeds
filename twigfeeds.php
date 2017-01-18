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

class TwigFeedsPlugin extends Plugin
{
	public static function getSubscribedEvents() {
		return [
			'onPageContentProcessed' => ['onPageContentProcessed', 0]
		];
	}
	public function onPageContentProcessed(Event $event) {
		if (!$this->isAdmin()) {
			$pluginsobject = (array) $this->config->get('plugins');
			$pluginsobject = $pluginsobject['twigfeeds'];
			if (isset($pluginsobject) && $pluginsobject['enabled']) {
				if (is_array($pluginsobject['twig_feeds'])) {
					$items = array();
					foreach ($pluginsobject['twig_feeds'] as $feed) {
						try {
							$reader = new Reader;
							$resource = $reader->download($feed['source']);
							$parser = $reader->getParser(
								$resource->getUrl(),
								$resource->getContent(),
								$resource->getEncoding()
							);
							$result = $parser->execute();
							$title = $result->getTitle();
							
							if ($feed['start']) {
								$start = $feed['start'];
							} else {
								$start = 0;
							}
							if ($feed['end']) {
								$end = $feed['end'];
							} else {
								$end = 500;
							}
							foreach ($result->items as $item) {
								$items[$title][] = $item;
								if (++$start == $end) break;
							}
						}
						catch (Exception $e) {
							$this->grav['debugger']->addMessage('Vendor-package PicoFeed threw an exception, check error logs.');
						}
					}
					$this->grav['twig']->twig_vars['twig_feeds'] = $items;
				}
			}
		}
	}
}