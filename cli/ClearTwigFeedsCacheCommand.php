<?php
namespace Grav\Plugin\Console;

use Grav\Common\Grav;
use Grav\Common\GravTrait;
use Grav\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use TwigFeeds\Utilities;

/**
 * TwigFeeds Clear Cache Command
 *
 * Class ClearTwigFeedsCacheCommand
 * @package Grav\Plugin\TwigFeedsPlugin
 * @license MIT License by Ole Vik
 * @since v3.0.0
 */
class ClearTwigFeedsCacheCommand extends ConsoleCommand
{
    /**
     * Declare command alias, description, and options
     */
    protected function configure()
    {
        $this
            ->setName('clearcache')
            ->setAliases(['clear-cache'])
            ->setDescription('Clears TwigFeeds cache')
            ->setHelp('The <info>clearcache</info> command deletes cached files from the active cache location');
    }

    /**
     * Runs functions to clear cache
     */
    protected function serve()
    {
        $config = $this->config();
        $this->removeCache($config);
    }

    /**
     * Declare config from plugin-config
     * @return array Plugin configuration
     */
    private function config()
    {
        $config = Grav::instance()['config']->get('plugins.twigfeeds');
        $config['locator'] = Grav::instance()['locator'];
        if ($config['static_cache']) {
            $config['cache_path'] = $config['locator']->findResource('user://data', true) . '/twigfeeds/';
        } else {
            $config['cache_path'] = $config['locator']->findResource('cache://', true) . '/twigfeeds/';
        }
        $config['blueprint_path'] = $config['locator']->findResource('user://plugins/twigfeeds/blueprints.yaml', true);
        return $config;
    }

    /**
     * Removes cache
     * @param array Plugin configuration
     */
    private function removeCache($config)
    {
        $this->output->writeln('');
        $this->output->writeln('<magenta>Clearing cached TwigFeeds data</magenta>');
        $this->output->writeln('');

        $utility = new Utilities($config);
        $call = $utility->bustCache();
        $this->output->writeln($call);
    }
}
