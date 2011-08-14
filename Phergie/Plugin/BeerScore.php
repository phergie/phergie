<?php
/**
 * Phergie
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://phergie.org/license
 *
 * @category  Phergie
 * @package   Phergie_Plugin_BeerScore
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_BeerScore
 */

/**
 * Handles incoming requests for beer scores.
 *
 * @category Phergie
 * @package  Phergie_Plugin_BeerScore
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_BeerScore
 * @uses     Phergie_Plugin_Http pear.phergie.org
 */
class Phergie_Plugin_BeerScore extends Phergie_Plugin_Abstract
{
    /**
     * Score result type
     *
     * @const string
     */
    const TYPE_SCORE = 'SCORE';

    /**
     * Search result type
     *
     * @const string
     */
    const TYPE_SEARCH = 'SEARCH';

    /**
     * Refine result type
     *
     * @const type
     */
    const TYPE_REFINE = 'REFINE';

    /**
     * Base API URL
     *
     * @const string
     */
    const API_BASE_URL = 'http://caedmon.net/beerscore/';

    /**
     * HTTP plugin
     *
     * @var Phergie_Plugin_Http
     */
    protected $http;

    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $this->http = $this->getPluginHandler()->getPlugin('Http');
    }

    /**
     * Handles beerscore commands.
     *
     * @param string $searchstring String to use in seaching for beer scores
     *
     * @return void
     */
    public function onCommandBeerscore($searchstring)
    {
        $event = $this->getEvent();
        $target = $event->getNick();
        $source = $event->getSource();

        $apiurl = self::API_BASE_URL . rawurlencode($searchstring);
        $response = $this->http->get($apiurl);

        if ($response->isError()) {
            $this->doNotice($target, 'Score not found (or failed to contact API)');
            return;
        }

        $result = $response->getContent();
        switch ($result->type) {
        case self::TYPE_SCORE:
            // small enough number to get scores
            foreach ($result->beer as $beer) {
                if ($beer->score === -1) {
                    $score = '(not rated)';
                } else {
                    $score = $beer->score;
                }
                $str
                    = "{$target}: rating for {$beer->name}" .
                    " = {$score} ({$beer->url})";
                $this->doPrivmsg($source, $str);
            }
            break;

        case self::TYPE_SEARCH:
            // only beer names, no scores
            $str = '';
            $found = 0;
            foreach ($result->beer as $beer) {
                if (isset($beer->score)) {
                    ++$found;
                    if ($beer->score === -1) {
                        $score = '(not rated)';
                    } else {
                        $score = $beer->score;
                    }
                    $str
                        = "{$target}: rating for {$beer->name}" .
                        " = {$score} ({$beer->url})";
                    $this->doPrivmsg($source, $str);
                } else {
                    $str .= "({$beer->name} -> {$beer->url}) ";
                }
            }
            $foundnum = $result->num - $found;
            $more = $found ? 'more ' : '';
            $str = "{$target}: {$foundnum} {$more}results... {$str}";
            $this->doPrivmsg($source, $str);
            break;

        case self::TYPE_REFINE:
            // Too many results; only output search URL
            if ($result->num < 100) {
                $num = $result->num;
            } else {
                $num = 'at least 100';
            }
            $resultsword = (($result->num > 1) ? 'results' : 'result');
            $str = "{$target}: {$num} {$resultsword}; {$result->searchurl}";
            $this->doPrivmsg($source, $str);
            break;
        }
    }
}
