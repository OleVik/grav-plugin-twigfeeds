<?php declare(strict_types=1);
/*
 * This file is part of the feed-io package.
 *
 * (c) Alexandre Debril <alex.debril@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FeedIo\Standard;

use DOMDocument;
use FeedIo\Reader\Document;
use FeedIo\RuleSet;
use FeedIo\Rule\Structure;

class Rdf extends Rss
{

    /**
     * Format version
     */
    const VERSION = '1.0';

    /**
     * RDF document must have a <rdf> root node
     */
    const ROOT_NODE_TAGNAME = 'rdf';

    /**
     * publication date
     */
    const DATE_NODE_TAGNAME = 'dc:date';

    /**
     * Tells if the parser can handle the feed or not
     * @param  Document $document
     * @return boolean
     */
    public function canHandle(Document $document) : bool
    {
        return false !== strpos($document->getDOMDocument()->documentElement->tagName, static::ROOT_NODE_TAGNAME);
    }

    /**
     * @param  DOMDocument $document
     * @return \DomElement
     */
    public function getMainElement(\DOMDocument $document) : \DOMElement
    {
        return $document->documentElement;
    }

    /**
     * @return RuleSet
     */
    public function buildFeedRuleSet() : RuleSet
    {
        $ruleSet = new RuleSet();
        $ruleSet->add(new Structure(static::CHANNEL_NODE_TAGNAME, $this->buildItemRuleSet()));

        return $ruleSet;
    }
}
