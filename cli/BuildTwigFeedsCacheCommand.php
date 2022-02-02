<?php

/**
 * TwigFeeds Plugin, Build Cache CLI
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

namespace Grav\Plugin\Console;

use DateTime;
use Grav\Common\Grav;
use Grav\Common\GravTrait;
use Grav\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Grav\Plugin\TwigFeedsPlugin\API\Parser;
use Grav\Plugin\TwigFeedsPlugin\API\Manifest;
use Grav\Plugin\TwigFeedsPlugin\Utilities;

/**
 * TwigFeeds Build Cache Command
 *
 * Class BuildTwigFeedsCacheCommand
 *
 * @category Extensions
 * @package  Grav\Plugin\TwigFeedsPlugin
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-twigfeeds
 */
class BuildTwigFeedsCacheCommand extends ConsoleCommand
{
    /**
     * Declare command alias, description, and options
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('buildcache')
            ->setAliases(['build-cache'])
            ->setDescription('Builds TwigFeeds cache')
            ->addOption('cache', null, InputOption::VALUE_NONE, 'If set builds to cache/twigfeeds/*')
            ->addOption('data', null, InputOption::VALUE_NONE, 'If set builds to user/data/twigfeeds/*')
            ->setHelp('The <info>buildcache</info> command builds the cache, by default to the active cache location');
    }

    /**
     * Runs functions to build cache
     *
     * @return void
     */
    protected function serve()
    {
        $config = $this->config();
        $this->buildManifest($config);
        $this->buildCache($config);
    }

    /**
     * Declare config from plugin-config
     *
     * @return array Plugin configuration
     */
    private function config()
    {
        $config = Grav::instance()['config']->get('plugins.twigfeeds');
        $config['locator'] = Grav::instance()['locator'];
        $config['config_file'] = $config['locator']->findResource('user://config', true) . '/plugins/twigfeeds.yaml';
        if ($config['static_cache']) {
            $config['cache_path'] = $config['locator']->findResource('user://data', true) . '/twigfeeds/';
        } else {
            $config['cache_path'] = $config['locator']->findResource('cache://', true) . '/twigfeeds/';
        }
        $config['blueprint_path'] = $config['locator']->findResource('user://plugins/twigfeeds/blueprints.yaml', true);
        return $config;
    }

    /**
     * Builds manifest
     *
     * @param array $config Plugin configuration
     *
     * @return void
     */
    private function buildManifest($config)
    {
        $this->output->writeln('');
        $this->output->writeln('<magenta>Building TwigFeeds manifest</magenta>');
        $this->output->writeln('');

        include __DIR__ . '/../vendor/autoload.php';
        $utility = new Utilities($config);
        $manifest = new Manifest($config, $utility);

        $manifestFile = $config['cache_path'] . 'manifest.json';
        if (!file_exists($manifestFile)) {
            $this->output->writeln('<white>Manifest does not exist, writing it</white>');
            $content = $manifest->manifestStructure($config['config_file']);
            $content['data'] = $config['twig_feeds'];
            $call = $manifest->writeManifest($manifestFile, $content);
            $this->output->writeln($call);
        } else {
            $content = $manifest->readManifest($manifestFile);
            $call = $manifest->compare($content);

            /* Pass manifest and configuration in their entirety */
            if ($call['state'] == 'changed') {
                $callback = $call['state'] . ' ' . $call['configFileDate'];
                $this->output->writeln('<white>Config (' . $callback . ') and manifest unequal, busting cache</white>');
                $call = $manifest->bustCache();
                $this->output->writeln($call);
                $this->output->writeln('<white>Renewing manifest</white>');
                $call = $manifest->writeManifest($manifestFile, $content);
                $this->output->writeln($call);
            } else {
                $this->output->writeln('<white>Config and manifest equal, continuing</white>');
            }
        }
    }

    /**
     * Builds cache
     *
     * @param array $config Plugin configuration
     *
     * @return void
     */
    private function buildCache($config)
    {
        $this->output->writeln('');
        $this->output->writeln('<magenta>Building TwigFeeds cache</magenta>');
        $this->output->writeln('');

        include __DIR__ . '/../vendor/autoload.php';
        $utility = new Utilities($config);
        $manifest = new Manifest($config, $utility);
        $parser = new Parser($config);
        $manifestFile = $config['cache_path'] . 'manifest.json';
        $content = $manifest->readManifest($manifestFile);
        foreach ($content['data'] as $entry => $data) {
            $data['title'] = $data['filename'];
            $data['source'] = $entry;
            $data['now'] = $utility->now;
            $data['cache'] = true;
            $path = $config['cache_path'] . $data['filename'];
            if (!$config['pass_headers']) {
                unset($data['etag']);
                unset($data['last_modified']);
            }
            if (!file_exists($path)) {
                $this->output->writeln('<white>Can\'t find ' . $data['filename'] . ', writing it</white>');
                $call = $parser->parseFeed($data, $path);
                $this->output->writeln('  ' . $call['callback']);
                $content['data'][$entry]['etag'] = $call['data']['etag'];
                $content['data'][$entry]['last_modified'] = $call['data']['last_modified'];
                $content['data'][$entry]['last_checked'] = $utility->now;
                $content['data'][$entry]['last_checked_date'] = $utility->humanDate($utility->now);
            } else {
                $then = $utility->humanDate($data['last_checked'] + $data['cache_time']);
                $now = $utility->humanDate($utility->now);
                if (($data['last_checked'] + $data['cache_time']) > $utility->now) {
                    $this->output->writeln('<yellow>' . $then . ' is after ' . $now . '</yellow>');
                    $this->output->writeln(' <white>Skip ' . $entry . '</white>');
                } else {
                    $this->output->writeln('<yellow>' . $then . ' is before ' . $now . '</yellow>');
                    $this->output->writeln('  <white>Download ' . $entry . '</white>');
                    $call = $parser->parseFeed($data, $path);
                    $this->output->writeln('  ' . $call['callback']);
                    $content['data'][$entry]['etag'] = $call['data']['etag'];
                    $content['data'][$entry]['last_modified'] = $call['data']['last_modified'];
                    $content['data'][$entry]['last_checked'] = $utility->now;
                    $content['data'][$entry]['last_checked_date'] = $utility->humanDate($utility->now);
                }
            }
        }
        $this->output->writeln('');
        $this->output->writeln('<white>Updating manifest</white>');
        $call = $manifest->updateManifest($manifestFile, $content);
        $this->output->writeln($call);
    }
}
