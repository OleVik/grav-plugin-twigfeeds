<?php

namespace TwigFeeds\Tests;

class Utilities
{
    public static $validateURLpattern = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|(([^\s()<>]+|(([^\s()<>]+)))*))+(?:(([^\s()<>]+|(([^\s()<>]+)))*)|[^\s`!()[]{};:'\".,<>?«»“”‘’]))/";

    /**
     * Output message to console
     *
     * @param  string $str
     * @return int|false
     */
    public static function output($str)
    {
        fwrite(STDERR, print_r($str . PHP_EOL, true));
    }

    /**
     * Validate URL against a pattern
     *
     * @param  string $url URL to validate
     * @return int 1 on success, 0 on failure
     *
     * @see https://stackoverflow.com/a/5289151
     */
    public static function validateURL($url)
    {
        return preg_match(self::$validateURLpattern, $url);
    }
}
