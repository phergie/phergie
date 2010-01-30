<?php

/**
 * Parses incoming requests for beer scores
 */
class Phergie_Plugin_BeerScore extends Phergie_Plugin_Abstract
{
    const TYPE_SCORE = 'SCORE';
    const TYPE_SEARCH = 'SEARCH';
    const TYPE_REFINE = 'REFINE';

    const API_BASE_URL = 'http://caedmon.net/beerscore/';

    public function onCommandBeerscore($searchstring)
    {
        $target = $this->getEvent()->getNick();
        $source = $this->getEvent()->getSource();

        $apiurl = self::API_BASE_URL . rawurlencode($searchstring);
        $result = json_decode(file_get_contents($apiurl));

        if (!$result || !isset($result->type) || !is_array($result->beer)) {
            $this->doNotice($target, 'Score not found (or failed to contact API)');
            return;
        }

        switch ($result->type) {
            case self::TYPE_SCORE:
                // small enough number to get scores
                foreach ($result->beer as $beer) {
                    if ($beer->score === -1) {
                        $score = '(not rated)';
                    } else {
                        $score = $beer->score;
                    }
                    $str = "{$target}: rating for {$beer->name} = {$score} ({$beer->url})";
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
                        $this->doPrivmsg($source, "{$target}: rating for {$beer->name} = {$score} ({$beer->url})");
                    } else {
                        $str .= "({$beer->name} -> {$beer->url}) ";
                    }
                }
                $foundnum = $result->num - $found;
                $more = $found ? 'more ' : '';
                $this->doPrivmsg($source, "{$target}: {$foundnum} {$more}results... {$str}");
                break;

            case self::TYPE_REFINE:
                // Too many results; only output search URL
                if ($result->num < 100) {
                    $num = $result->num;
                } else {
                    $num = 'at least 100';
                }
                $resultsword = (($result->num > 1) ? 'results' : 'result');
                $this->doPrivmsg($source, "{$target}: {$num} {$resultsword}; {$beer->searchurl}");
                break;
        }
    }
}

// vim: language=php tab=4 et indent=4
