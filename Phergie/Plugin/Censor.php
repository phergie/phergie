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
 * @package   Phergie_Plugin_Censor
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Censor
 */

/**
 * Facilitates censoring of event content or discardment of events
 * containing potentially offensive phrases depending on the value of the
 * configuration setting censor.mode ('off', 'censor', 'discard'). Also
 * provides access to a web service for detecting censored words so that
 * other plugins may optionally integrate and adjust behavior accordingly to
 * prevent discardment of events.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Censor
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Censor
 * @uses     extension soap
 */
class Phergie_Plugin_Censor extends Phergie_Plugin_Abstract
{
    /**
     * SOAP client to interact with the CDYNE Profanity Filter API
     *
     * @var SoapClient
     */
    protected $soap;

    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        if (!extension_loaded('soap')) {
            $this->fail('The PHP soap extension is required');
        }

        if (!in_array($this->config['censor.mode'], array('censor', 'discard'))) {
            $this->plugins->removePlugin($this);
        }
    }

    /**
     * Returns a "clean" version of a given string.
     *
     * @param string $string String to clean
     *
     * @return string Cleaned string
     */
    public function cleanString($string)
    {
        if (empty($this->soap)) {
            $this->soap = new SoapClient('http://ws.cdyne.com/ProfanityWS/Profanity.asmx?wsdl');
        }
        $params = array('Text' => $string);
        $attempts = 0;
        while ($attempts < 3) {
            try {
                $response = $this->soap->SimpleProfanityFilter($params);
                break;
            } catch (SoapFault $e) {
                $attempts++;
                sleep(1);
            }
        }
        if ($attempts == 3) {
            return $string;
        }
        return $response->SimpleProfanityFilterResult->CleanText;
    }

    /**
     * Processes events before they are dispatched and either censors their
     * content or discards them if they contain potentially offensive
     * content.
     *
     * @return void
     */
    public function preDispatch()
    {
        $events = $this->events->getEvents();

        foreach ($events as $event) {
            switch ($event->getType()) {
                case Phergie_Event_Request::TYPE_PRIVMSG:
                case Phergie_Event_Request::TYPE_ACTION:
                case Phergie_Event_Request::TYPE_NOTICE:
                    $text = $event->getArgument(1);
                    $clean = $this->cleanString($text);
                    if ($text != $clean) {
                        if ($this->config['censor.mode'] == 'censor') {
                            $event->setArgument(1, $clean);
                        } else {
                            $this->events->removeEvent($event);
                        }
                    }
                    break;
            }
        }
    }
}
