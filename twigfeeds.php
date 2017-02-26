<?php
namespace Grav\Plugin;

use DateTime;
use Grav\Common\Data;
use Grav\Common\Cache;
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
			'onTwigPageVariables' => ['onTwigSiteVariables', 0],
			'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
		];
	}
	public function onTwigSiteVariables(Event $event) {
		if (!$this->isAdmin()) {
			$pluginsobject = (array) $this->config->get('plugins');
			$pluginsobject = $pluginsobject['twigfeeds'];
			if (isset($pluginsobject) && $pluginsobject['enabled']) {
				if ($pluginsobject['static_cache']) {
					$cache_path = $this->grav['locator']->findResource('user://data', true) . DIRECTORY_SEPARATOR . 'twigfeeds' . DIRECTORY_SEPARATOR;
				} else {
					$cache_path = CACHE_DIR . 'twigfeeds' . DIRECTORY_SEPARATOR;
				}
				if (is_array($pluginsobject['twig_feeds'])) {
					if ($pluginsobject['cache']) {
						$manifest_file = $cache_path . 'manifest.json';
						if (!file_exists($manifest_file)) {
							/* Build Manifest */
							$manifest = array();
							foreach ($pluginsobject['twig_feeds'] as $feed) {
								$manifest[$feed['source']]['etag'] = '';
								$manifest[$feed['source']]['last_modified'] = '';
								$manifest[$feed['source']]['filename'] = '';
							}
							$file = File::instance($manifest_file);
							$file->save(json_encode($manifest, JSON_PRETTY_PRINT));
						} else {
							/* Read Manifest */
							$manifest_file = $cache_path . 'manifest.json';
							$manifest = array();
							$file = File::instance($manifest_file);
							$manifest_json = json_decode($file->content());
							foreach ($manifest_json as $entry => $data) {
								if (isset($data->timestamp)) {
									$date = DateTime::createFromFormat('U', (string) $data->timestamp);
								} else {
									$date = new DateTime('now');
								}
								$last_modified = $date->format(DateTime::RSS);
								$manifest[$entry]['etag'] = $data->etag;
								$manifest[$entry]['last_modified'] = $date->format(DateTime::RSS);
								$manifest[$entry]['filename'] = $data->filename;
							}
						}
					}
					
					$feed_items = array();
					foreach ($pluginsobject['twig_feeds'] as $feed) {
						$filename = parse_url($feed['source'], PHP_URL_HOST) . '.json';
						$items = array();
						try {
							$config = new Config;
							$config->setTimezone('UTC');
							$reader = new Reader($config);
							
							if ($pluginsobject['cache']) {
								$last_modified = $manifest[$feed['source']]['last_modified'];
								$etag = $manifest[$feed['source']]['etag'];
								
								$resource = $reader->download($feed['source'], $last_modified, $etag);
							} else {
								$resource = $reader->download($feed['source']);
							}
							if ($resource->isModified()) {
								$parser = $reader->getParser(
									$resource->getUrl(),
									$resource->getContent(),
									$resource->getEncoding()
								);
								$result = $parser->execute();
								$title = $result->getTitle();
								$source = $feed['source'];
							}
							
							if ($pluginsobject['cache']) {
								$etag = $resource->getEtag();
								$last_modified = $resource->getLastModified();
								
								if (!empty($last_modified)) {
									$date_object = DateTime::createFromFormat(DateTime::RSS, $last_modified);
								} else {
									$date_object = new DateTime('now');
								}
								$timestamp = $date_object->getTimestamp();
								
								/* Update Manifest.json */
								$manifest[$feed['source']]['etag'] = $etag;
								$manifest[$feed['source']]['timestamp'] = $timestamp;
								$manifest[$feed['source']]['filename'] = $filename;
								$file = File::instance($manifest_file);
								$file->save(json_encode($manifest));
							}
							
							if ($resource->isModified()) {
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
								
								$feed_items[$name]['title'] = $title;
								$feed_items[$name]['source'] = $source;
								$feed_items[$name]['start'] = $start;
								$feed_items[$name]['end'] = $end;
								$feed_items[$name]['amount'] = $amount;
								foreach ($result->items as $item) {
									$feed_items[$name]['items'][] = (array) $item;
									if (++$start == $end) break;
								}
								$data = $feed_items;
								if ($pluginsobject['debug'] && $this->config->get('system')['debugger']['enabled']) {
									$this->grav['debugger']->addMessage(array('state' =>'modified', 'type' => gettype($data), 'action' => 'add to feed_items: ' . $filename, $data));
									$this->grav['log']->debug('Twig Feeds: ' . $filename . ', state: modified, type: ' . gettype($data) . ', action: add to feed_items');
								}
								
								if ($pluginsobject['cache']) {
									/* Add custom name to feed before saving */
									$feed_items[$name]['name'] = $name;
									/* Save results */
									$file = File::instance($cache_path . '' . $filename);
									$file->save(json_encode($feed_items[$name], JSON_PRETTY_PRINT));
								}
							} else {
								$file = File::instance($cache_path . '' . $filename);
								$data = json_decode($file->content());
								if ($data->name) {
									$name = $data->name;
								}
								else {
									$name = $data->title;
								}
								$feed_items[$name] = $data;
								if ($pluginsobject['debug'] && $this->config->get('system')['debugger']['enabled']) {
									$this->grav['debugger']->addMessage(array('state' =>'cached', 'type' => gettype($data), 'action' => 'add to feed_items: : ' . $filename, $data));
									$this->grav['log']->debug('Twig Feeds: ' . $filename . ', state: cached, type: ' . gettype($data) . ', action: add to feed_items');
								}
							}
						}
						catch (PicoFeedException $e) {
							$this->grav['debugger']->addMessage('PicoFeed threw an exception: ' . $e);
							$this->grav['log']->error('PicoFeed threw an exception: ' . $e);
						}
						catch (Exception $e) {
							$this->grav['debugger']->addMessage('Twig Feeds-plugin threw an exception: ' . $e);
							$this->grav['log']->error('Twig Feeds-plugin threw an exception: ' . $e);
						}
					}
					if (!isset($this->grav['twig']->twig_vars['twig_feeds'])) {
						$this->grav['twig']->twig_vars['twig_feeds'] = $feed_items;
					}
				}
			}
		}
	}
}
