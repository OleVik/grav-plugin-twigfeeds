<?php declare(strict_types=1);
/*
 * This file is part of the feed-io package.
 *
 * (c) Alexandre Debril <alex.debril@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FeedIo\Adapter\FileSystem;

use FeedIo\Adapter\ClientInterface;
use FeedIo\Adapter\NotFoundException;
use FeedIo\Adapter\ResponseInterface;

/**
 * Filesystem client
 */
class Client implements ClientInterface
{

    /**
     * @param  string                            $path
     * @param  \DateTime                         $modifiedSince
     * @throws \FeedIo\Adapter\NotFoundException
     * @return \FeedIo\Adapter\ResponseInterface
     */
    public function getResponse(string $path, \DateTime $modifiedSince) : ResponseInterface
    {
        if (file_exists($path)) {
            return new Response(
                file_get_contents($path),
                new \DateTime('@'.filemtime($path))
                );
        }

        throw new NotFoundException($path);
    }
}
