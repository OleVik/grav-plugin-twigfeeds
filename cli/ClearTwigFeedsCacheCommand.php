<?php

/**
 * TwigFeeds Plugin, Clear Cache CLI
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

use Grav\Common\Grav;
use Grav\Common\GravTrait;
use Grav\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Grav\Plugin\TwigFeedsPlugin\Utilities;

/**
 * TwigFeeds Clear Cache Command
 *
 * Class ClearTwigFeedsCacheCommand
 *
 * @category Extensions
 * @package  Grav\Plugin\TwigFeedsPlugin
 * @author   Ole Vik <git@olevik.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/OleVik/grav-plugin-twigfeeds
 */
class ClearTwigFeedsCacheCommand extends ConsoleCommand
{
    /**
     * Declare command alias, description, and options
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('clear')
            ->setAliases(['clear-cache', 'clearcache'])
            ->setDescription('Clears TwigFeeds cache')
            ->addOption('cache', null, InputOption::VALUE_NONE, 'If set, clears grav/cache/twigfeeds/*')
            ->addOption('data', null, InputOption::VALUE_NONE, 'If set, clears to grav/user/data/twigfeeds/*')
            ->setHelp('The <info>clear</info> command deletes cached files from the active or selected cache-location');
    }

    /**
     * Runs functions to clear cache
     *
     * @return void
     */
    protected function serve()
    {
        $config = $this->config();
        $this->removeCache($config);
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
        if ($config['static_cache'] || $this->input->getOption('data')) {
            $config['cache_path'] = $config['locator']->findResource('user://data', true) . '/twigfeeds/';
        } elseif ($config['static_cache'] === false || $this->input->getOption('cache')) {
            $config['cache_path'] = $config['locator']->findResource('cache://', true) . '/twigfeeds/';
        } else {
            $config['cache_path'] = $config['locator']->findResource('cache://', true) . '/twigfeeds/';
        }
        $config['blueprint_path'] = $config['locator']->findResource('user://plugins/twigfeeds/blueprints.yaml', true);
        return $config;
    }

    /**
     * Removes cache
     *
     * @param array $config Plugin configuration
     *
     * @return void
     */
    private function removeCache($config)
    {
        include __DIR__ . '/../vendor/autoload.php';
        $this->output->writeln('');
        $this->output->writeln('<magenta>Clearing cached TwigFeeds data</magenta>');
        $this->output->writeln('');

        $utility = new Utilities($config);
        $call = $utility->bustCache();
        $this->output->writeln($call);
    }
}
